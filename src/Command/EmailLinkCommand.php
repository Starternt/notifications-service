<?php

namespace App\Command;

use App\Utils\KafkaHelper;
use http\Exception\RuntimeException;
use Swift_Mailer;
use Swift_Message;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Twig\Environment;

/**
 * @package App\Command
 */
class EmailLinkCommand extends Command
{
    use KafkaHelper;

    /**
     * @var string
     */
    protected static $defaultName = 'email:link';

    /**
     * @var string
     */
    protected $kafkaHost;

    /**
     * @var string
     */
    protected $kafkaPort;

    /**
     * @var Swift_Mailer
     */
    private $mailer;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @inheritDoc
     */
    protected function configure()
    {
        $this->setDescription('Run kafka listener for sending emails with activation links');
    }

    /**
     * @param string $kafkaHost
     * @param string $kafkaPort
     * @param Environment $twig
     * @param Swift_Mailer $mailer
     */
    public function __construct(
        string $kafkaHost = '',
        string $kafkaPort = '',
        Environment $twig,
        Swift_Mailer $mailer
    ) {
        parent::__construct();

        $this->kafkaHost = $kafkaHost;
        $this->kafkaPort = $kafkaPort;
        $this->mailer = $mailer;
        $this->twig = $twig;
    }

    /**
     * @inheritDoc
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $consumer = $this->configureConsumer($this->kafkaHost, $this->kafkaPort, 'notification-activation-links');
        $consumer->start(
            function ($topic, $part, $message) {
                $value = json_decode($message['message']['value'], true);
                $link = 'http://localhost:1060/users-api/v1/users/activate?token='
                    .$value['token']; // todo move to config

                $message = new Swift_Message();

                $data = [
                    'link' => $link,
                ];

                $message
                    ->setSubject('Activation link')
                    ->setFrom('test@gmail.com')
                    ->setTo(
                        $value['email']
                    )
                    ->addPart(
                        $this->twig->render('activation_link.html.twig', $data),
                        'text/html',
                        'UTF-8'
                    );

                $results = $this->mailer->send($message);
                if (0 === $results) {
                    throw new RuntimeException(
                        sprintf(
                            "Failed to send email to user %s",
                            $value['email']
                        )
                    );
                }

            }
        );

        return 1;
    }
}
