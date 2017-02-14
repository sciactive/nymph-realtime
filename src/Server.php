<?php namespace Nymph\PubSub;

use \Ratchet\Server\IoServer;
use \Ratchet\Http\HttpServer;
use \Ratchet\WebSocket\WsServer;
use \SciActive\RequirePHP;

class Server {
  private $loop;
  private $logger;
  private $writer;
  private $router;
  private $server;

  /**
   * Apply configuration to Nymph-PubSub.
   *
   * $config should be an associative array of Nymph-PubSub configuration. Use
   * the following form:
   *
   * [
   *     'driver' => 'MySQL',
   *     'pubsub' => true,
   *     'MySql' => [
   *         'host' => '127.0.0.1'
   *     ]
   * ]
   *
   * @param array $config An associative array of Nymph's configuration.
   */
  public static function configure($config = []) {
    RequirePHP::_('NymphPubSubConfig', [], function () use ($config) {
      $defaults = include dirname(__DIR__).'/conf/defaults.php';
      $nymphConfig = [];
      foreach ($defaults as $curName => $curOption) {
        if ((array) $curOption === $curOption && isset($curOption['value'])) {
          $nymphConfig[$curName] = $curOption['value'];
        } else {
          $nymphConfig[$curName] = [];
          foreach ($curOption as $curSubName => $curSubOption) {
            $nymphConfig[$curName][$curSubName] = $curSubOption['value'];
          }
        }
      }
      return array_replace_recursive($nymphConfig, $config);
    });
  }

  public function __construct($config = []) {
    self::configure($config);
    $config = RequirePHP::_('NymphPubSubConfig');

    // $this->loop = \React\EventLoop\Factory::create();

    // Create a logger which writes everything to the STDOUT
    $this->logger = new \Zend\Log\Logger();
    $this->writer = new \Zend\Log\Writer\Stream("php://output");
    $this->logger->addWriter($this->writer);

    // Create a WebSocket server using SSL
    try {
      $this->logger->notice(
          "Nymph-PubSub server starting on {$config['host']}:{$config['port']}."
      );
    } catch (\Exception $e) {
      if (strpos($e->getMessage(), 'date.timezone')) {
        echo "It looks like you haven't set a default timezone. In order to " .
            "avoid constant complaints from Zend's logger, I'm just going to " .
            "kill myself now.\n\n";
        echo $e->getMessage()."\n";
        exit;
      }
      throw $e;
    }
    // $this->server = new WebSocketServer(
    //     "tcp://{$config['host']}:{$config['port']}",
    //     $this->loop,
    //     $this->logger
    // );
    //
    // $server->run();
    //
    // // Create a router which transfers all /chat connections to the
    // // MessageHandler class
    // $this->router = new ClientRouter($this->server, $this->logger);
    //
    // // route / url
    // $this->router->addRoute('#^/$#i', new MessageHandler($this->logger));
    //
    // // route unmatched urls
    // $this->router->addRoute(
    //     '#^(.*)$#i',
    //     new MessageHandlerForUnroutedUrls($this->logger)
    // );
    //
    // // Bind the server
    // $this->server->bind();

    $this->server = IoServer::factory(
        new HttpServer(
            new WsServer(
                new MessageHandler()
            )
        ),
        $config['port']
    );
  }

  public function __destruct() {
    try {
      $this->logger->notice("Nymph-PubSub server shutting down.");
    } catch (\Exception $e) {
      if (strpos($e->getMessage(), 'date.timezone')) {
        // Already echoed an error about this.
        exit;
      }
      throw $e;
    }
    // $this->server->removeAllListeners();
    $this->stop();
  }

  public function run() {
    // Start the event loop
    $this->server->run();
  }

  public function stop() {
    // Stop the event loop
    // $this->loop->stop();
  }
}
