<?php

namespace Flamix\App24Core\Controllers;

use App\Exceptions\App24Exception;
use Bitrix24\Bitrix24;
use Bitrix24\User\User;
use Flamix\App24Core\Models\Portals;
use Flamix\App24Core\User24;
use Flamix\App24Core\App24;

class UserController
{
    /**
     * Retrieve everything for a specific user (we only have saved data for the portal).
     *
     * @return Bitrix24 Connector for a specific portal
     */
    private static function getAuth(): Bitrix24
    {
        return User24::getInstance()->getConnect();
    }

    /**
     * Is the current user an admin?
     *
     * @return mixed
     */
    public static function isAdmin(): bool
    {
        return boolval((new User(self::getAuth()))->admin());
    }

    /**
     * Get the ID of the current user
     *
     * @return int
     */
    public static function getID(): int
    {
        $obUser = new User(self::getAuth());
        $user = $obUser->current();

        return intval($user['result']['ID'] ?? 0);
    }

    /**
     * User who installed the portal.
     *
     * @param int $portal_id
     * @return int
     * @throws App24Exception
     * @throws \Bitrix24\Bitrix24Exception
     */
    public static function getPortalMainUserId(int $portal_id = 0): int
    {
        $portal_id = $portal_id ?: Portals::getId();
        if (!$portal_id) {
            return 0;
        }

        $user_id = Portals::getByID($portal_id)->user_id ?? 0;
        if ($user_id) {
            return $user_id;
        }

        // Access the admin specifically since getAuth() of this class provides us the access of CURRENT user
        $obUser24 = new User(App24::getInstance($portal_id)->getConnect());
        $arCurrentUser24 = $obUser24->current();

        return intval($arCurrentUser24['result']['ID'] ?? 0);
    }

    /**
     * Find and update the ID of the user who installed the portal in the portals database.
     *
     * @param int $portal_id
     * @return int
     * @throws App24Exception
     * @throws \Bitrix24\Bitrix24Exception
     */
    public static function updateOrCreateMainUserPortal(int $portal_id): int
    {
        $user_id = self::getPortalMainUserId($portal_id);
        if (!$user_id) {
            return 0;
        }

        $portal = Portals::find($portal_id);
        $portal->user_id = $user_id;
        $portal->save();

        return $user_id;
    }
}