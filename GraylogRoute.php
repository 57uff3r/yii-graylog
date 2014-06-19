<?php
/**
 * Send Yii log messages to Graylog server
 *
 * @author Andrey Korchak <57uff3r@gmail.com>
 * @copyright Andrey Korchak, 2014
 */

class GraylogRoute extends CLogRoute
{
    /**
     * List of excluded categories from config
     * @var string
     */
    public $exclude = [];

    /**
     * Address of graylog server
     * @var string
     */
    public $server = '';

    /**
     * Curl
     * @var object
     */
    private $ch;

    /*
     * Graylog log message is limited to length of 1024 bytes.
     * We have to cut long log messages
     * @var int
     */
    public $maxMessageSize = 768;

    /**
     * Comparison of Yii and Graylog log-level codes.
     * @var array
     */
    private $logLevelCodes = [
        'info'    => 6,
        'trace'   => 6,
        'profile' => 6,
        'warning' => 4,
        'error'   => 0
    ];

    /**
     * Setup connection with graylog server
     * @author Andrey Korchak <ak@budist.ru>
     */
    public function init()
    {
        parent::init();

        if (false === filter_var($this->server, FILTER_VALIDATE_URL)) {
            throw new \CException('GrayLogRoute: you have to specify URL in config');
        }

        $this->ch = curl_init($this->server);
        curl_setopt($this->ch, CURLOPT_POST, 1);
        curl_setopt($this->ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
        curl_setopt($this->ch, CURLOPT_HTTP_VERSION, CURL_HTTP_VERSION_1_0);

        $this->exclude = preg_split('/[\s,]+/', strtolower($this->exclude), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Processes log messages and sends them to specific destination.
     * Derived child classes must implement this method.
     * @param array $logs list of messages. Each array element represents one message
     * with the following structure:
     * array(
     *   [0] => message (string)
     *   [1] => level (string)
     *   [2] => category (string)
     *   [3] => timestamp (float, obtained by microtime(true));
     *
     */
    public function processLogs($logs)
    {
        if (!empty($logs)) {
            foreach ($logs as $log) {
                if (!array_key_exists($log[1], $this->logLevelCodes) || $this->isExcluded($log[2])) {
                    continue;
                }

                $ready_log_records = [
                    'host'          => gethostbyname(gethostname()),
                    'level'         => $this->logLevelCodes[$log[1]],
                    'short_message' => 'Yii: '.$log[2].' '.$log[1],
                    'full_message'  => substr($log[0], 0, $this->maxMessageSize),
                    '_category'     => $log[2]
                ];

                curl_setopt($this->ch, CURLOPT_POSTFIELDS, (string)json_encode($ready_log_records));
                curl_exec($this->ch);
            }
        }
    }

    /**
     * Is message category excluded from logs?
     * @param string $categoryName
     * @return bool
     *
     */
    private function isExcluded($categoryName)
    {
        foreach ($this->exclude as $category) {
            $cat = strtolower($categoryName);
            if ($cat === $category || (($c = rtrim($category, '.*')) !== $category && strpos($cat, $c) === 0)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Close curl connection
     *
     */
    public function __destruct()
    {
        curl_close($this->ch);
    }
}
