<?php

namespace App;

// --- Konfigurace ---
const BASE_URL = 'https://area51.serverzone.dev/robot/';
const EMAIL = 'soowitchs@gmail.com';
const RETRY_MAX = 10;

class RobotNavigator
{
    private string $baseUrl;
    private string $email;
    private int $retryMax;
    public function __construct(string $baseUrl = BASE_URL, string $email = EMAIL, int $retryMax = RETRY_MAX)
    {
        $this->baseUrl = $baseUrl;
        $this->email = $email;
        $this->retryMax = $retryMax;
    }

    public function apiRequest(string $method, string $url, array $body = null)
    {
        for ($tries = 0; $tries < $this->retryMax; $tries++) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

            if ($body !== null) {
                $json = json_encode($body);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_HTTPHEADER, [
                    'Content-Type: application/json',
                    'Content-Length: ' . strlen($json),
                ]);
            }

            $resp = curl_exec($ch);
            $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $err = curl_error($ch);
            curl_close($ch);

            if ($code === 200)
                return json_decode($resp, true);
            if ($code === 410)
                throw new \Exception("Robot vyčerpal energii (410).");
        }

        throw new \Exception("API request failed after " . $this->retryMax . " tries: HTTP {$code}, response: {$resp}");
    }

    public function move(string $id, string $direction, int $distance): int
    {
        $url = $this->baseUrl . "{$id}/move";
        $body = ['direction' => $direction, 'distance' => $distance];
        $res = $this->apiRequest('PUT', $url, $body);
        return $res['distance'];
    }

    public function moveUntilBlocked(string $id, string $direction): int
    {
        $total = 0;
        while (true) {
            $moved = $this->move($id, $direction, 5);
            echo "Moved {$direction} {$moved} m\n";
            if ($moved === 0)
                break;
            $total += $moved;
        }
        return $total;
    }

    public function moveToCenter(string $id, int $x, int $y): void
    {
        foreach ([['right', $x], ['up', $y]] as [$dir, $dist]) {
            while ($dist > 0) {
                $step = min(5, $dist);
                $moved = $this->move($id, $dir, $step);
                echo "Moved {$dir} {$moved} m\n";
                $dist -= $moved;
            }
        }
    }

    public function measureHall(string $id): array
    {
        $maxW = $maxH = 0;
        foreach (['right', 'left', 'up', 'down'] as $dir) {
            $distance = $this->moveUntilBlocked($id, $dir);
            if (in_array($dir, ['right', 'left'])) {
                $maxW = max($maxW, $distance);
            } else {
                $maxH = max($maxH, $distance);
            }
        }
        return [$maxW, $maxH];
    }

    public function start()
    {
        $robot = $this->apiRequest('POST', $this->baseUrl, ['email' => $this->email]);
        $robotId = $robot['id'];
        echo "Robot ID: {$robotId}\n";

        echo "Měření rozměrů…\n";
        [$W, $H] = $this->measureHall($robotId);
        echo "Rozměry haly: W={$W} m, H={$H} m\n";

        $xC = floor($W / 2);
        $yC = floor($H / 2);
        echo "Střed je přibližně na x={$xC}, y={$yC}\n";

        echo "Navigace do středu…\n";
        $this->moveToCenter($robotId, $xC, $yC);

        echo "Escape…\n";
        $response = $this->apiRequest('PUT', $this->baseUrl . "{$robotId}/escape", ['salary' => 60000]);

        if (!empty($response['success'])) {
            echo "Únik proběhl úspěšně! 🎉\n";
        } else {
            echo "Escape selhal.\n";
        }
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'])) {
    (new RobotNavigator())->start();
}

