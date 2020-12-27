<?php

namespace App\Command\Twitch;

use App\TwitchChatClient;
use Minicli\Command\CommandController;

class DefaultController extends CommandController
{
    public function handle()
    {
        $this->getPrinter()->info("Starting Chatbot for Twitch...");

        $app = $this->getApp();

        $twitchUser = $app->config->twitch_user;
        $twitchOauth = $app->config->twitch_oauth;

        if (!$twitchUser OR !$twitchOauth) {
            $this->getPrinter()->error("Missing twitch config settings.");
            return;
        }

        $client = new TwitchChatClient($twitchUser, $twitchOauth);
        $client->connect();

        if (!$client->isConnected()) {
            $this->getPrinter()->error("It was not possible to connect server.");
            return;
        }

        $this->getPrinter()->info("Connected on server.\n");

        while (true) {
            
            $content = $client->read(512);

            if (strstr($content, 'PING')) {
                $client->send('PONG :tmi.twitch.tv');
                continue;
            }

            if (strstr($content, 'PRIVMSG')) {
                $this->easterEggMessage($content, $client);
                $this->printMessage($content);
                continue;
            }

            sleep(5);
        }
    }

    public function printMessage($rawMessage)
    {
        $parts = explode(":", $rawMessage, 3);
        $partsNickname = explode("!", $parts[1]);

        $nick = $partsNickname[0];
        $message = $parts[2];

        $nicknameStyle = "info";

        if ($nick === $this->getApp()->config->twitchUser) {
            $nicknameStyle = "info_alt";
        }

        $this->getPrinter()->out($nick, $nicknameStyle);
        $this->getPrinter()->out(': ');
        $this->getPrinter()->out($message);
        $this->getPrinter()->newline();
    }

    private function easterEggMessage(string $message, TwitchChatClient $client): void {

        if (strpos($message, "php") !== false) {
            $client->joinChannel($this->getApp()->config->channel_name);
            $client->send("PRIVMSG #{$this->getApp()->config->twitch_user} :Xeroque roumes of PHP is here ;) \r\n");
        }
    }
}