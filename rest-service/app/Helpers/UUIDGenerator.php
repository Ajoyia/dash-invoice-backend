<?php


namespace App\Helpers;

class UUIDGenerator
{
    private static $counter = 0;
    /**
     * Generates a UUID that embeds the current Unix time in milliseconds.
     *
     * This method converts the current Unix time in milliseconds into 100‑ns intervals,
     * adds the UUID epoch offset, and then splits the resulting 60‑bit timestamp into
     * the appropriate time fields.
     *
     * [@return](profile/return) string A valid UUID in the canonical 8-4-4-4-12 format.
     */
    public static function generateUUID()
    {
        return self::_generateUUID();
    }

    private static function _generateUUID()
    {
        // 1. Get the current Unix time in milliseconds.
        $unix_ms = (int)(microtime(true) * 1000);

        // 2. Convert milliseconds to 100‑ns intervals and add the offset.
        // There are 10,000 100‑ns intervals in one millisecond.
        $uuid_timestamp = ($unix_ms);

        // 3. Split the 60‑bit timestamp into its three parts.
        $time_low  = $uuid_timestamp & 0xffff;
        $time_mid  = ($uuid_timestamp>>16) & 0xffff;
        $time_hi  = ($uuid_timestamp >> 32) & 0xffffffff;

        self::$counter++;
        self::$counter = self::$counter % 65536;

        // 5. Generate a random 48‑bit node (typically a MAC address, here random).
        $node = '';

        for ($i = 0; $i < 6; $i++) {
            $node .= sprintf('%02x', random_int(0, 255));
        }

        // 6. Assemble the UUID in the standard 8-4-4-4-12 format.
        $uuid = sprintf(
            '%08x-%04x-%04x-%04x-%012s',


            $time_hi,         // 8 hex digits (includes version)
            $time_mid,                // 4 hex digits
            $time_low,             // 4 hex digits
            self::$counter,        // 2 hex digits
            $node                  // 12 hex digits
        );

        return $uuid;
    }
}