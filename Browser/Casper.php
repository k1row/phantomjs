<?php
namespace Browser;

/**
 * CasperJS wrapper
 *
 * installation:
 * 1 - install phantomJS: http://phantomjs.org/download.html
 * 2 - install CasperJS: http://casperjs.org/installation.html
 *
 * @author aguidet
 *
 */
class Casper
{
    private $_TAG_CURRENT_URL = '[CURRENT_URL]';
    private $_TAG_CURRENT_TITLE = '[CURRENT_TITLE]';
    private $_TAG_CURRENT_PAGE_CONTENT ='[CURRENT_PAGE_CONTENT]';
    private $_TAG_CURRENT_HTML ='[CURRENT_HTML]';

    private $_debug = true;
    private $_script = '';
    private $_output = array();
    private $_requestedUrls = array();
    private $_currentUrl = '';
    private $_userAgent = 'casper';

    /**
     * Set the UserAgent
     * @param string $userAgent
     */
    public function setUserAgent($userAgent)
    {
        $this->_userAgent = $userAgent;
    }

    /**
     * enable debug logging into syslog
     *
     * @param unknown $debug
     * @return Casper
     */
    public function setDebug($debug)
    {
        $this->_debug = $debug;
        return $this;
    }

    /**
     *
     * @return boolean
     */
    public function isDebug()
    {
        return $this->_debug;
    }

    /**
     * @param array $output
     * @return Casper
     */
    private function _setOutput($output)
    {
        $this->_output = $output;
        return $this;
    }

    /**
     * @return array
     */
    public function getOutput()
    {
        return $this->_output;
    }
    /**
     * clear the current casper script
     */
    private function _clear()
    {
        $this->_script = '';
        $this->_output = array();
        $this->_requestedUrls = array();
        $this->_currentUrl = '';
    }

    /**
     * open the specified url
     *
     * @param unknown $url
     *
     * @return \Browser\Casper
     */
    public function start($url)
    {
        $this->_clear();

        $fragment =<<<FRAGMENT
var x = require('casper').selectXPath;

var casper = require('casper').create({
    verbose: true,
    logLevel: 'debug',
    colorizerType: 'Dummy'
});

casper.userAgent('$this->_userAgent');
casper.start().then(function() {
    this.open('$url', {
        headers: {
            'Accept': 'text/html'
        }
    });
});

FRAGMENT;

        $this->_script = $fragment;

        return $this;
    }

    /**
     * Open URL after the initial opening
     * @param $url
     * @return $this
     */
    public function thenOpen($url)
    {
        $fragment =<<<FRAGMENT
casper.thenOpen('$url');

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    /**
     * fill the form with the array of data
     * then submit it if submit is true
     *
     * @param unknown $selector
     * @param unknown $data
     * @param string $submit
     *
     * @return \Browser\Casper
     */
    public function fillForm($selector, $data = array(), $submit = false)
    {
        $jsonData = json_encode($data);
        $jsonSubmit = ($submit) ? 'true' : 'false';

        $fragment =<<<FRAGMENT
casper.then(function () {
    this.fill('$selector', $jsonData, $jsonSubmit);
});

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    /**
     * wait until the text $text
     * appear on the page
     *
     * @param string $text
     * @param integer $timeout
     *
     * @return \Browser\Casper
     */
    public function waitForText($text, $timeout=5000)
    {
        $fragment =<<<FRAGMENT
casper.waitForText(
    '$text',
    function () {
        this.echo('found text "$text"');
    },
    function () {
        this.echo('timeout occured');
    },
    $timeout
);

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    /**
     * wait until timeout
     *
     * @param number $timeout
     * @return \Browser\Casper
     */
    public function wait($timeout=5000)
    {
        $fragment =<<<FRAGMENT
casper.wait(
    $timeout,
    function () {
        this.echo('timeout occured');
    }
);

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    /**
     * wait until the text $text
     * appear on the page
     *
     * @param string $text
     *
     * @return \Browser\Casper
     */
    public function waitForSelector($selector, $timeout=5000)
    {
        $fragment =<<<FRAGMENT
casper.waitForSelector(
    '$selector',
    function () {
        this.echo('found selector "$selector"');
    },
    function () {
        this.echo('timeout occured');
    },
    $timeout
);

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    public function waitForxPassSelector($selector, $timeout=5000)
    {
        $fragment =<<<FRAGMENT
casper.waitForSelector(
    x('$selector'),
    function () {
        this.echo('found selector "$selector"');
    },
    function () {
        this.echo('timeout occured');
    },
    $timeout
);

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    /**
     *
     * @param unknown $selector
     *
     * @return \Browser\Casper
     */
    public function click($selector)
    {
        $fragment =<<<FRAGMENT
casper.then(function() {
    this.click('$selector');
});

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    public function xpassClick($selector)
    {
        $fragment =<<<FRAGMENT

casper.thenClick(x('$selector'),
 function() {
    console.log("xpassClick !");
});

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    /**
     * take a screenshot of the page
     * area containing the selector
     *
     * @param string $selector
     * @param string $filename
     *
     * @return \Browser\Casper
     */
    public function captureSelector($selector, $filename)
    {
        $fragment =<<<FRAGMENT
casper.then(function() {
    this.captureSelector('$filename', '$selector');
});

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }


    /**
     * take a screenshot of the page
     * area defined by
     * array(top left width height)
     *
     * @param array $area
     * @param string $filename
     *
     * @return \Browser\Casper
     */
    public function capture(array $area, $filename)
    {
        $top    = $area['top'];
        $left   = $area['left'];
        $width  = $area['width'];
        $height = $area['height'];

        $fragment =<<<FRAGMENT
casper.then(function() {
    this.capture('$filename', {
        top: $top,
        left: $left,
        width: $width,
        height: $height
    });
});

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    /**
     * switch to the child frame number $id
     *
     * @param unknown $id
     * @return \Browser\Casper
     */
    public function switchToChildFrame($id)
    {
        $fragment =<<<FRAGMENT
casper.then(function() {
    this.page.switchToChildFrame($id);
});

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    /**
     * get back to parent frame
     *
     * @return \Browser\Casper
     */
    public function switchToParentFrame()
    {
        $fragment =<<<FRAGMENT
casper.then(function() {
    this.page.switchToParentFrame();
});

FRAGMENT;

        $this->_script .= $fragment;

        return $this;

    }


    public function evaluate($function)
    {
        $fragment =<<<FRAGMENT
casper.then(function() {
    casper.evaluate(function() {
        $function
    });
});

FRAGMENT;

        $this->_script .= $fragment;

        return $this;
    }

    public function login($org_id, $org_pass, $id_name, $pass_name, $submit_name)
    {
        $fragment =<<<FRAGMENT
casper.then(function(){
    this.evaluate(function(id, pass, id_name, pass_name, submit_name) {
        document.querySelector('$id_name').value = id;
        document.querySelector('$pass_name').value = pass;
        document.querySelector('$submit_name').click();
    }, "$org_id", "$org_pass");
});

FRAGMENT;

        $this->_script .= $fragment;
        return $this;
    }

    /**
     * run the casperJS script and return the stdOut
     * in using the output variable
     *
     * @return array
     */
    public function run()
    {
        $output = array();

        $fragment =<<<FRAGMENT

casper.then(function () {
    this.echo('$this->_TAG_CURRENT_URL' + this.getCurrentUrl());
    this.echo('$this->_TAG_CURRENT_TITLE' + this.getTitle());
    this.echo('$this->_TAG_CURRENT_PAGE_CONTENT' + this.getPageContent());
    this.echo('$this->_TAG_CURRENT_HTML' + this.getHTML());
});


casper.run();

FRAGMENT;

        $this->_script .= $fragment;

        $filename = '/tmp/php-casperjs-' . uniqid() . '.js';
        file_put_contents($filename, $this->_script);
        $cmd = 'PHANTOMJS_EXECUTABLE="/usr/local/bin/phantomjs" /usr/local/bin/casperjs ' . $filename;
        var_dump($cmd);
        exec($cmd, $output);

        $this->_setOutput($output);
        $this->_processOutput();

        //unlink($filename);
        return $output;
    }

    public function getHTML() {
        $fragment =<<<FRAGMENT

casper.then(function () {
    this.echo('$this->_TAG_CURRENT_HTML' + this.getHTML());
});

FRAGMENT;

        $this->_script .= $fragment;
        return $this;
    }

    /**
     * process the output after navigation
     * and fill the differents attributes for
     * later usage
     */
    private function _processOutput()
    {
        foreach ($this->getOutput() as $outputLine) {
            if (strpos($outputLine, $this->_TAG_CURRENT_URL) !== false) {
                $this->_currentUrl = str_replace($this->_TAG_CURRENT_URL, '', $outputLine);
            }

            if (strpos($outputLine, "Navigation requested: url=") !== false) {

                $frag0 = explode('Navigation requested: url=', $outputLine);
                $frag1 = explode(', type=', $frag0[1]);
                $this->_requestedUrls[] = $frag1[0];
            }

            if ($this->isDebug()) {
                syslog(LOG_INFO, '[PHP-CASPERJS] ' . $outputLine);
            }
        }
    }

    public function getCurrentUrl()
    {
        return $this->_currentUrl;
    }

    public function getRequestedUrls()
    {
        return $this->_requestedUrls;
    }
}
