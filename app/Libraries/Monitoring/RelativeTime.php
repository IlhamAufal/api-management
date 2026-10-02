<?php

namespace App\Libraries\Monitoring;

/**
 * Waktu relatif untuk dashboard ("2 jam lalu").
 */
class RelativeTime
{
    public static function format(?string $datetime, ?string $now = null): string
    {
        if ($datetime === null || $datetime === '') {
            return 'belum pernah';
        }

        $then = strtotime($datetime);
        $nowTs = strtotime($now ?? date('Y-m-d H:i:s'));

        if ($then === false || $nowTs === false) {
            return $datetime;
        }

        $seconds = $nowTs - $then;

        if ($seconds < 0) {
            $seconds = 0;
        }

        if ($seconds < 60) {
            return 'baru saja';
        }

        $minutes = intdiv($seconds, 60);
        if ($minutes < 60) {
            return $minutes . ' menit lalu';
        }

        $hours = intdiv($minutes, 60);
        if ($hours < 24) {
            return $hours . ' jam lalu';
        }

        $days = intdiv($hours, 24);
        if ($days < 30) {
            return $days . ' hari lalu';
        }

        return date('d M Y', $then);
    }
}
