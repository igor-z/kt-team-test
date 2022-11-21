<?php

namespace App\Tests;

use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

trait SessionAwareTrait
{
    private function startSession(KernelBrowser $client): Session
    {
        $session = new Session(new MockArraySessionStorage());
        $session->start();

        $client->getCookieJar()->set(new Cookie($session->getName(), $session->getId()));

        return $session;
    }

    private function getSession(KernelBrowser $client): Session
    {
        $client->getCookieJar()->get();
    }
}