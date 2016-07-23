<?php
/**
 * P4PHP Perforce connection implementation.
 *
 * This client implementation provides access to the P4PHP extension in a way
 * that conforms to P4_Connection_Interface. This allows the P4PHP extension
 * and the Perforce Command-Line Client wrapper to be used interchangeably.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection_Extension extends P4_Connection_Abstract
{
    protected   $_instance;

    /**
     * Constructs a P4 connection object.
     *
     * @param   string  $port        optional - the port to connect to.
     * @param   string  $user        optional - the user to connect as.
     * @param   string  $client      optional - the client spec to use.
     * @param   string  $password    optional - the password to use.
     * @param   string  $ticket      optional - a ticket to use.
     */
    public function __construct(
        $port       = null,
        $user       = null,
        $client     = null,
        $password   = null,
        $ticket     = null )
    {
        // ensure that p4-php is installed.
        if (!extension_loaded('perforce')) {
            throw new P4_Exception(
                'Cannot create P4 API extension instance. Perforce extension not loaded.');
        }

        // create an instance of p4-php.
        $this->_instance = new P4;

        // disable automatic sequence expansion (call expandSequences on result object if desired)
        $this->_instance->expand_sequences = false;

        // prevent command exceptions from being thrown by P4.
        // we throw our own so that we can attach the result.
        $this->_instance->exception_level = 0;

        parent::__construct($port, $user, $client, $password, $ticket);
    }

    /**
     * Disconnect from the Perforce Server.
     *
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function disconnect()
    {
        // call parent to run disconnect callbacks.
        parent::disconnect();

        if ($this->isConnected()) {
            $this->_instance->disconnect();
        }

        return $this;
    }

    /**
     * Check connected state.
     *
     * @return  bool    true if connected, false otherwise.
     */
    public function isConnected()
    {
        return $this->_instance->connected();
    }

    /**
     * Extends parent to set our instance's password to the returned
     * ticket value if login succeeds.
     *
     * @return  string|null     the ticket issued by the server or null if
     *                          no ticket issued (ie. user has no password).
     * @throws  P4_Connection_LoginException    if login fails.
     */
    public function login()
    {
        $ticket = parent::login();

        if ($ticket) {
            $this->_instance->password = $ticket;
        }

        return $ticket;
    }

    /**
     * Extend set port to update p4-php.
     *
     * @param   string  $port   the port to connect to.
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function setPort($port)
    {
        parent::setPort($port);
        $this->_instance->port = $this->getPort();

        return $this;
    }

    /**
     * Extend set user to update p4-php.
     *
     * @param   string  $user           the user to connect as.
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function setUser($user)
    {
        parent::setUser($user);
        $this->_instance->user = $this->getUser();

        return $this;
    }

    /**
     * Extend set client to update p4-php.
     *
     * @param   string  $client             the name of the client workspace to use.
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function setClient($client)
    {
        parent::setClient($client);

        // if no client is specified, normally the host name is used.
        // this can collide with an existing depot or client name, so
        // we use a temp id to avoid errors.
        $this->_instance->client = $this->getClient() ?: P4_Client::makeTempId();

        return $this;
    }

    /**
     * Extend set password to update p4-php.
     *
     * @param   string  $password   the password to use as authentication.
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function setPassword($password)
    {
        parent::setPassword($password);
        $this->_instance->password = $this->getPassword();

        return $this;
    }

    /**
     * Extend set ticket to update p4-php.
     * Note: the ticket is stored in the password field in p4-php.
     *
     * @param   string  $ticket     the ticket to use as authentication.
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function setTicket($ticket)
    {
        parent::setTicket($ticket);
        if ($ticket) {
            $this->_instance->password = $this->getTicket();
        }

        return $this;
    }

    /**
     * Extended to set charset in p4-php.
     * Sets the character set to use for this perforce connection.
     *
     * You should only set a character set when connecting to a
     * 'unicode enabled' server, or when setting the special value
     * of 'none'.
     *
     * @param   string  $charset            the charset to use (e.g. 'utf8').
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function setCharset($charset)
    {
        $this->_instance->charset = $charset;

        return parent::setCharset($charset);
    }

    /**
     * Extended to set host name in p4-php.
     * Sets the client host name overriding the environment.
     *
     * @param   string|null $host           the host name to use.
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function setHost($host)
    {
        $this->_instance->host = $host;

        return parent::setHost($host);
    }

    /**
     * Extended to set app name in p4-php.
     * Set the name of the application that is using this connection.
     *
     * @param   string|null     $name       the app name to report to the server.
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function setAppName($name)
    {
        $this->_instance->set_protocol('app', (string) $name);

        return parent::setAppName($name);
    }

    /**
     * Get the identity of this Connection implementation.
     *
     * Resulting array will contain:
     *  - name
     *  - platform
     *  - version    (p4-php version)
     *  - build      (p4-php build)
     *  - apiversion (p4-api version)
     *  - apibuild   (p4-api build)
     *  - date
     *  - original   (all text following 'Rev. ' from original response)
     *
     * @return  array           an array of client Connection information
     * @throws  P4_Exception    if the returned version string is invalid
     */
    public function getConnectionIdentity()
    {
        // obtain the extension's identification
        $output = $this->_instance->identify();

        // extract the version string and split into components
        preg_match('/\nRev. (.*)\.$/', $output, $matches);
        $parts = isset($matches[1]) ? preg_split('/\/| \(| API\) \(|\)/', $matches[1]) : null;
        if (count($parts) < 8) {
            $message = 'p4php returned an invalid version string';
            throw new P4_Exception($message);
        }

        // build identity array of version components, including original string
        $identity = array(
            'name'       => $parts[0],
            'platform'   => $parts[1],
            'version'    => $parts[2],
            'build'      => $parts[3],
            'apiversion' => $parts[4],
            'apibuild'   => $parts[5],
            'date'       => $parts[6] . '/' . $parts[7] . '/' . $parts[8],
            'original'   => $matches[1]
        );

        return $identity;
    }

    /**
     * Actually issues a command. Called by run() to perform the dirty work.
     *
     * @param   string          $command    the command to run.
     * @param   array           $params     optional - arguments.
     * @param   array|string    $input      optional - input for the command - should be provided
     *                                      in array form when writing perforce spec records.
     * @param   boolean         $tagged     optional - true/false to enable/disable tagged output.
     *                                      defaults to true.
     * @return  P4_Result the perforce result object.
     */
    protected function _run($command, $params = array(), $input = null, $tagged = true)
    {
        // push command to front of parameters array
        array_unshift($params, $command);

        // set input for the command.
        if ($input !== null) {
            $this->_instance->input = $input;
        }

        // toggle tagged output.
        $this->_instance->tagged = (bool) $tagged;

        // establish connection to perforce server.
        if (!$this->isConnected()) {
            $this->connect();
        }

        // run command.
        $data = call_user_func_array(array($this->_instance, "run"), $params);

        // collect data in result object and ensure output is in array form.
        $result = new P4_Result($command, $data, $tagged);
        $result->setErrors($this->_instance->errors);
        $result->setWarnings($this->_instance->warnings);

        return $result;
    }

    /**
     * Prepare input for passing to the p4 extension.
     * Ensure input is either a string or an array of strings.
     *
     * @param   string|array    $input      the input to prepare for p4.
     * @param   string          $command    the command to prepare input for.
     * @return  string|array    the prepared input.
     */
    protected function _prepareInput($input, $command)
    {
        // if input is not an array, cast to string and return.
        if (!is_array($input)) {
            return (string) $input;
        }

        // ensure each element of array is a string.
        $stringify = function(&$input)
        {
            $input = (string) $input;
        };
        array_walk_recursive($input, $stringify);

        return $input;
    }


    /**
     * Does real work of establishing connection. Called by connect().
     *
     * @throws  P4_Connection_ConnectException  if the connection fails.
     */
    protected function _connect()
    {
        // temporarily enable exceptions to catch connection failure.
        $this->_instance->exception_level = 1;
        try {
            $this->_instance->connect();
            $this->_instance->exception_level = 0;
        } catch (P4_Exception $e) {
            $this->_instance->exception_level = 0;
            throw new P4_Connection_ConnectException(
                "Connect failed: " . $e->getMessage()
            );
        }
    }
}
