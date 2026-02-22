<?php

namespace Flamix\App24Core\Middleware;

use App\Exceptions\App24Exception;
use Illuminate\Contracts\Session\Session;
use Illuminate\Http\Request;
use Illuminate\Session\Middleware\StartSession as IlluminateStartSession;
use Symfony\Component\HttpFoundation\Response;

/**
 * StartSession by URL. Needed to correct iFrame session handling.
 * Inspired - https://github.com/iMi-digital/laravel-transsid
 */
class StartSession extends IlluminateStartSession
{
    const LOCKED_FIELD = 'locked_to';

    /**
     * Store IP and Agent in order to lock the session to a specfic user
     * (against over-taking via URL sharing)
     *
     * @param $session
     * @param $request
     */
    protected function lockToUser($session, $request)
    {
        $session->put(self::LOCKED_FIELD, [
            'ip' => $request->getClientIp(),
            'agent' => md5($request->server('HTTP_USER_AGENT'))
        ]);
    }

    /**
     * Check if IP or Agent changed
     *
     * @param $session
     * @param $request
     * @return bool
     */
    protected function validate($session, $request)
    {
        $locked = $session->get(self::LOCKED_FIELD);
        return !($locked['ip'] != $request->getClientIp() || $locked['agent'] != md5($request->server('HTTP_USER_AGENT')));
    }

    /**
     * Overwritten from parent class.
     *
     * @param  Request  $request
     * @return Session|mixed
     */
    public function getSession(Request $request)
    {
        $session = parent::getSession($request);

        if ($id = $this->resolveSessionParameter($request, $session)) {
            $session->setId($id);

            if (!$session->has(self::LOCKED_FIELD)) {
                $this->lockToUser($session, $request);
                $this->putSessionToFakeCookie($request, $session);
            } else {
                // validate session against store IP and user agent hash
                if (!$this->validate($session, $request)) {
                    $session->setId(null); // refresh ID
                    $session->start();
                    $this->lockToUser($session, $request);
                }
            }
        }

        return $session;
    }

    protected function addCookieToResponse(Response $response, Session $session)
    {
        // Do not add cookie if TransSID is active
        if ($session->has(self::LOCKED_FIELD)) {
            return;
        }

        parent::addCookieToResponse($response, $session);
    }

    /**
     * Get session ID from request. Validation in Laravel isValidId($id)
     *
     * @param $request
     * @param $session
     * @return string
     * @throws \Throwable
     */
    protected function resolveSessionParameter($request, $session): string
    {
        $session_name = $session->getName();

        // Check if session name is valid! In browser we can not use dots in session name.
        throw_if(str_contains($session_name, '.'), App24Exception::class, 'Session name should not contain dots. Please check your session configuration (session -> cookie)');

        if ($request->has($session_name)) {
            return $request->input($session_name);
        }

        if ($request->hasHeader('x-session')) {
            return $request->header('x-session');
        }
    }

    /**
     * Extra security check for session.
     *
     * @param  Request  $request
     * @return void
     * @throws App24Exception
     */
    public function checkSession(Request $request)
    {
        $session = session();
        if ($session->has(self::LOCKED_FIELD) && !$this->validate($session, $request)) {
            $session->flush();
            throw new App24Exception('It looks like your session has expired. Please reload page and try again.');
        }
    }

    /**
     * Store the session ID into a fake cookie in the request object.
     *
     * Hack! Whitout this, the session ID will be regenerated on every request, because it's expected to be in the cookies.
     *
     * @param  Request  $request  The current request object.
     * @param  Session  $session  The session containing the ID to store in the cookie.
     *
     * @return void
     */
    private function putSessionToFakeCookie(Request $request, Session $session): void
    {
        $request->cookies->set($session->getName(), $session->getId());
    }
}