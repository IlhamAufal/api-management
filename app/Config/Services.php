<?php

namespace Config;

use CodeIgniter\Config\BaseService;

/**
 * Services Configuration file.
 *
 * Services are simply other classes/libraries that the system uses
 * to do its job. This is used by CodeIgniter to allow the core of the
 * framework to be swapped out easily without affecting the usage within
 * the rest of your application.
 *
 * This file holds any application-specific services, or service overrides
 * that you might need. An example has been included with the general
 * method format you should use for your service methods. For more examples,
 * see the core Services file at system/Config/Services.php.
 */
class Services extends BaseService
{
    /**
     * Introspeksi schema source aktif (dipakai registry watched tables).
     * Shared supaya test bisa Services::injectMock('sourceIntrospector', ...).
     */
    public static function sourceIntrospector(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('sourceIntrospector');
        }

        return new \App\Libraries\Monitoring\SourceIntrospector();
    }

    /**
     * Checker data-freshness (monitoring matrix & pasca-simpan registry).
     * Shared supaya test bisa Services::injectMock('freshnessChecker', ...).
     */
    public static function freshnessChecker(bool $getShared = true)
    {
        if ($getShared) {
            return static::getSharedInstance('freshnessChecker');
        }

        return new \App\Libraries\Monitoring\TableFreshnessChecker();
    }
}
