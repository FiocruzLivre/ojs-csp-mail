<?php

/**
 * @file plugins /generic/cspMail/CspMailPlugin.inc.php
 *
 * Copyright (c) 2024 Lívia Gouvêa
 * Distributed under the GNU GPL v3. For full terms see the file docs/COPYING.
 *
 * @class CspUserPlugin
 * @brief Customizes User profile fields
 */


namespace APP\plugins\generic\cspMail;

use Illuminate\Support\Facades\Event;
use APP\plugins\generic\cspMail\MailChange;
use PKP\plugins\GenericPlugin;

class CspMailPlugin extends GenericPlugin
{
    public function register($category, $path, $mainContextId = NULL)
    {
        $success = parent::register($category, $path);

        if ($success && $this->getEnabled()) {
            Event::subscribe(new MailChange());
        }

        return $success;
    }
    /**
     * Provide a name for this plugin
     *
     * The name will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDisplayName()
    {
        return __('plugins.generic.cspMail.displayName');
    }

    /**
     * Provide a description for this plugin
     *
     * The description will appear in the Plugin Gallery where editors can
     * install, enable and disable plugins.
     */
    public function getDescription()
    {
        return __('plugins.generic.cspMail.description');
    }
}
