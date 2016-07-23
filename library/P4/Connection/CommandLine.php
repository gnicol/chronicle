<?php
/**
 * Perforce Command Line Client.
 *
 * A PHP Wrapper for the Perforce Command-Line Client (P4).
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class P4_Connection_CommandLine extends P4_Connection_Abstract
{
    const       E_EMPTY         = 0;  // nothing yet
    const       E_INFO          = 1;  // something good happened
    const       E_WARN          = 2;  // something not good happened
    const       E_FAILED        = 3;  // user did something wrong
    const       E_FATAL         = 4;  // system broken -- nothing can continue

    // important shell exit codes.
    const       CMD_CANNOT_EXEC = 126;
    const       CMD_NOT_FOUND   = 127;

    const       P4_BINARY       = 'p4';

    protected   $_p4Path        = null;
    private     $_isConnected   = false;

    /**
     * Get the identity of this Connection implementation.
     *
     * Resulting array will contain:
     *  - name
     *  - platform
     *  - version
     *  - build
     *  - apiversion (same value as version, included for consistency)
     *  - apibuild   (same value as build, included for consistency)
     *  - date
     *  - original   (all text following 'Rev. ' from original response)
     *
     * @return  array           an array of client Connection information
     * @throws  P4_Exception    if the returned version string is invalid
     */
    public function getConnectionIdentity()
    {
        // obtain version output from p4 command
        exec($this->_getP4Path() .' -V 2>&1', $output, $returnVar);
        if ($returnVar != 0) {
            $message = "Unable to exec() the 'p4' command ("
                     . "return: " . $returnVar . ").";
            throw new P4_Exception($message);
        }

        // extract the composed version string and split it into components
        preg_match('/Rev. (.*)\.$/', array_pop($output), $matches);
        $parts = isset($matches[1]) ? preg_split('/\/| \(|\)/', $matches[1]) : null;
        if (count($parts) < 6) {
            $message = 'p4 returned an invalid version string';
            throw new P4_Exception($message);
        }

        // build identity array of version components, including original string
        $identity = array(
            'name'       => $parts[0],
            'platform'   => $parts[1],
            'version'    => $parts[2],
            'build'      => $parts[3],
            'apiversion' => $parts[2],
            'apibuild'   => $parts[3],
            'date'       => $parts[4] . '/' . $parts[5] . '/' . $parts[6],
            'original'   => $matches[1]
        );
        return $identity;
    }

    /**
     * Pretend to disconnect - set the connected flag to false.
     *
     * @return  P4_Connection_Interface     provides fluent interface.
     */
    public function disconnect()
    {
        // call parent to run disconnect callbacks.
        parent::disconnect();

        $this->_isConnected = false;

        return $this;
    }

    /**
     * Check connected state.
     *
     * @return  bool    true if connected, false otherwise.
     */
    public function isConnected()
    {
        return $this->_isConnected;
    }

    /**
     * Set the full path/filename to the p4 executable.
     *
     * @param   string|null     $path   the full path/filename to p4.
     */
    public function setP4Path($path)
    {
        if (!is_string($path) && !is_null($path)) {
            throw new InvalidArgumentException(
                "Cannot set p4 path. Path must be a string or null"
            );
        }

        $this->_p4Path = $path;
    }

    /**
     * Escape a string for use as a command argument.
     *
     * Replacement for the default escapeshellarg() function.
     * In Windows, we use the bypass_shell option for proc_open, which changes
     * the rules for escaping command line arguments.
     *
     * @param string $arg       the string to escape
     * @return string           the escaped string
     */
    public static function escapeArg($arg)
    {
        // if not windows, exit early with normal escapeshellarg
        if (!P4_Environment::isWindows()) {
            return escapeshellarg($arg);
        }

        // As per MS spec: http://msdn.microsoft.com/en-us/library/a1y7w461.aspx
        // escape quotes and backslashes in command line arguments.

        // step 1: escape backslashes immediately preceeding double quotes
        $arg = preg_replace('/(\\\\+)"/', '\\1\\1"', $arg);
        // step 2: escape backslashes at the end of string (protects our added quotes)
        $arg = preg_replace('/(\\\\+)$/', '\\1\\1',  $arg);
        // step 3: escape double quotes
        $arg = preg_replace('/"/',        '\\"',     $arg);
        // step 4: wrap the result in double quotes
        $arg = '"' . $arg . '"';

        return $arg;
    }

    /**
     * Provide our own escapeshellcmd to support platform-specific functionality.
     *
     * @param string $cmd   The command to escape.
     * @return string       The escaped command.
     */
    public static function escapeShellCmd($cmd)
    {
        // if not windows, exit early with normal escapeshellcmd
        if (!P4_Environment::isWindows()) {
            return escapeshellcmd($cmd);
        }

        return static::escapeArg($cmd);
    }

    /**
     * Get the maximum allowable length of all command arguments.
     *
     * @return  int     the max length of combined arguments - zero for no limit
     */
    public function getArgMax()
    {
        // return the system arg-max less a Kilobyte for our arguments
        return P4_Environment::getArgMax() - 1024;
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
        // escape parameters for safe shell execution.
        for ($i = 0; $i < count($params); $i++) {
            $params[$i] = static::escapeArg($params[$i]);
        }

        // build up the full p4 command.
        $p4 = static::escapeShellCmd($this->_getP4Path());
        if ($this->getPort()) {
            $p4 .= ' -p ' . static::escapeArg($this->getPort());
        }
        if ($this->getUser()) {
            $p4 .= ' -u ' . static::escapeArg($this->getUser());
        }
        if ($this->getTicket()) {
            $p4 .= ' -P ' . static::escapeArg($this->getTicket());
        }
        if (!$this->getTicket() && $this->_password ) {
            $p4 .= ' -P ' . static::escapeArg($this->_password);
        }
        if ($this->getCharset()) {
            $p4 .= ' -C ' . static::escapeArg($this->getCharset());
        }
        if ($this->getHost()) {
            $p4 .= ' -H ' . static::escapeArg($this->getHost());
        }
        if ($this->getAppName()) {
            $p4 .= ' -Z ' . static::escapeArg('app=' . $this->getAppName());
        }
        if ($tagged) {
            $p4 .= ' -Ztag';
        }

        // if no client is specified, normally the host name is used.
        // this can collide with an existing depot or client name, so
        // we use a temp id to avoid errors.
        $client = $this->getClient() ?: P4_Client::makeTempId();
        $p4 .= ' -c ' . static::escapeArg($client);

        $p4 .= ' -Mp ';  // use serialized PHP input/output.
        $p4 .= ' ' . static::escapeArg($command) . ' ' . implode(' ', $params);

        // log the full p4 command
        $message = "P4 (" . spl_object_hash($this) . ") execute p4: " . $p4;
        P4_Log::log(
            substr($message, 0, static::LOG_MAX_STRING_LENGTH),
            P4_Log::DEBUG
        );

        // create a temporary file name to store stderr output.
        $stdErrFile = tempnam(sys_get_temp_dir(), "stderr");

        // define descriptors for proc_open communication.
        //  0 - stdin
        //  1 - stdout
        //  2 - stderr
        $descriptors = array(
            0 => array("pipe", "rw"),
            1 => array("pipe", "w"),
            2 => array("file", $stdErrFile, "w"));

        // launch the process.
        $pipes   = array();
        $process = proc_open(
            $p4,
            $descriptors,
            $pipes,
            NULL,
            NULL,
            array(
                'bypass_shell'  => P4_Environment::isWindows()
            )
        );

        // check for proc_open error.
        if (!is_resource($process)) {
            $message = "Unable to proc_open() p4 ('$p4').";
            throw new P4_Exception($message);
        }

        // if input provided, write it to stdin.
        if ($input !== null) {
            fwrite($pipes[0], $input);
            fwrite($pipes[0], "\n");
            fflush($pipes[0]);
        }

        // read out pipes and close them.
        @fclose($pipes[0]);
        $stdOut = stream_get_contents($pipes[1]);
        $stdErr = file_get_contents($stdErrFile);
        @fclose($pipes[1]);
        @fclose($pipes[2]);
        $status = proc_close($process);
        unlink($stdErrFile);

        // check for shell exec problems.
        if ($status === self::CMD_CANNOT_EXEC || $status === self::CMD_NOT_FOUND) {
            $message = "Unable to execute p4 ('" . trim($stdErr) . "').";
            throw new P4_Exception($message);
        }

        // check for usage error.
        if (stristr($stdErr, "invalid option")) {
            throw new P4_Exception("Usage error: " . $stdErr);
        }

        // check for connection error.
        if (stristr($stdErr, "connect to server failed")
            || preg_match("/TCP connect to .+ failed/", $stdErr)
        ) {
            $this->_isConnected = false;
            throw new P4_Connection_ConnectException("Connect failed: " . $stdErr);
        } else {
            $this->_isConnected = true;
        }

        // unserialize output into a perforce result object.
        $result = new P4_Result($command, null, $tagged);
        $output = $this->_unserializeOutput($stdOut);

        // ensure that output unserializes to an array.
        if (!is_array($output)) {
            $message = "Command failed. Output did not deserialize into an array.";
            $e = new P4_Connection_CommandException($message);
            $e->setConnection($this);
            $e->setResult($result);
            throw $e;
        }

        // put data into result set.
        // separate errors and warnings from data.
        foreach ($output as $data) {
            switch ($data['code']) {
                case 'error':
                    if ($data['severity'] > self::E_WARN) {
                        $result->addError($data['data']);
                    } else {
                        $result->addWarning($data['data']);
                    }
                    break;
                case 'text':
                case 'binary':
                case 'info':
                    $result->addData($data['data']);
                    break;
                default:
                    unset($data['code']);
                    $result->addData($data);
                    break;
            }
        }

        // check for output on stderr and add to result object.
        if ($stdErr && $status) {
            $result->addError($stdErr);
        }

        return $result;
    }

    /**
     * Return the path & filename to p4.
     *
     * If p4 path is explicitly set via setP4Path(), returns that value.
     * Otherwise, checks for a P4_PATH constant or a P4_PATH environment
     * variable before falling back to 'p4'.
     *
     * @return  string  the path and filename to p4.
     */
    private function _getP4Path()
    {
        if ($this->_p4Path) {
            return $this->_p4Path;
        } else if (defined('P4_PATH')) {
            return P4_PATH;
        } else if (getenv('P4_PATH')) {
            return getenv('P4_PATH');
        } else {
            return self::P4_BINARY;
        }
    }

    /**
     * Prepare input for passing to the p4 via stdin.
     *
     * If input is an array, serialize it to a string suitable
     * for passing to the p4 client as marshalled PHP input. If the
     * array is multi-dimensional, flatten it.
     *
     * In the special case of the 'password' command, convert to a
     * string by imploding with newlines rather than serializing.
     *
     * @param   string|array    $input      the input to prepare for p4.
     * @param   string          $command    the command to prepare input for.
     * @return  string          the serialized output string.
     */
    protected function _prepareInput($input, $command)
    {
        // if input is not an array, don't serialize it.
        if (!is_array($input)) {
            return $input;
        }

        // if command is 'password', convert to string via implode.
        if ($command == "password" || $command == "passwd") {
            return implode("\n", $input) . "\n";
        }

        // flatten input array and cast values to strings.
        $flatInput = array();
        foreach ($input as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $subKey => $subValue) {
                    $flatInput[$key . $subKey] = (string) $subValue;
                }
            } else {
                $flatInput[$key] = (string) $value;
            }
        }

        // serialize and return.
        return serialize($flatInput);
    }

    /**
     * Unserialize output and expand numbered sequences into arrays.
     * This is needed to match the behavior of P4PHP.
     *
     * @param   string  $output     the output string to unserialize and expand.
     * @return  array   the unserialized output array.
     */
    private function _unserializeOutput($output)
    {
        // don't attempt to unserialize null output.
        if ($output == null) {
            return null;
        }

        $output = unserialize(trim($output));

        // ensure we always return an array
        return is_array($output) ? $output : array();
    }

    /**
     * Does real work of establishing connection. Called by connect().
     *
     * The command-line wrapper does not maintain a persistent connection.
     * But, it can use 'p4 info' to test the connection parameters.
     *
     * @throws  P4_Connection_ConnectException  if the connection fails.
     */
    protected function _connect()
    {
        // info will trigger a connect exception if connect to server fails.
        $this->_run('info');
        $this->_isConnected = true;
    }
}
