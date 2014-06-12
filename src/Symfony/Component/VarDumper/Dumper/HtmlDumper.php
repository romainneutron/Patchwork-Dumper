<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Component\VarDumper\Dumper;

/**
 * HtmlDumper dumps variables as HTML.
 *
 * @author Nicolas Grekas <p@tchwork.com>
 */
class HtmlDumper extends CliDumper
{
    protected $dumpHeader = '';
    protected $dumpPrefix = '<pre class=sf-var-debug style=white-space:pre>';
    protected $dumpSuffix = '</pre>';
    protected $colors = true;
    protected $headerIsDumped = false;
    protected $lastDepth = -1;
    protected $styles = array(
        'num'       => 'font-weight:bold;color:#0087FF',
        'const'     => 'font-weight:bold;color:#0087FF',
        'str'       => 'font-weight:bold;color:#00D7FF',
        'cchr'      => 'font-style: italic',
        'note'      => 'color:#D7AF00',
        'ref'       => 'color:#444444',
        'public'    => 'color:#008700',
        'protected' => 'color:#D75F00',
        'private'   => 'color:#D70000',
        'meta'      => 'color:#005FFF',
    );

    /**
     * {@inheritdoc}
     */
    public function __construct($outputStream = null)
    {
        parent::__construct($outputStream);

        if (!isset($this->dumpHeader)) {
            $this->setStyles($this->styles);
        }
    }

    /**
     * {@inheritdoc}
     */
    public function setLineDumper($callback)
    {
        $this->headerIsDumped = false;

        return parent::setLineDumper($callback);
    }

    /**
     * {@inheritdoc}
     */
    public function setStyles(array $styles)
    {
        $this->headerIsDumped = false;
        $this->styles = $styles + $this->styles;
    }

    /**
     * Sets an HTML header the will be dumped once in the output stream.
     *
     * @param string $header An HTML string.
     */
    public function setDumpHeader($header)
    {
        $this->dumpHeader = $header;
    }

    /**
     * Sets an HTML prefix and suffix that will encapse every single dump.
     *
     * @param string $prefix The prepended HTML string.
     * @param string $suffix The appended HTML string.
     */
    public function setDumpBoudaries($prefix, $suffix)
    {
        $this->dumpPrefix = $prefix;
        $this->dumpSuffix = $suffix;
    }

    /**
     * Dumps the HTML header.
     */
    protected function dumpHeader()
    {
        $this->headerIsDumped = true;

        $p = 'sf-var-debug';
        $this->line .= '<style>';
        parent::dumpLine(0);
        $this->line .= "a.$p-ref {{$this->styles['ref']}}";
        parent::dumpLine(0);

        foreach ($this->styles as $class => $style) {
            $this->line .= "span.$p-$class {{$style}}";
            parent::dumpLine(0);
        }

        $this->line .= '</style>';
        parent::dumpLine(0);
        $this->line .= $this->dumpHeader;
        parent::dumpLine(0);
    }

    /**
     * {@inheritdoc}
     */
    protected function style($style, $val)
    {
        if ('ref' === $style) {
            $ref = substr($val, 1);
            if ('#' === $val[0]) {
                return "<a class=sf-var-debug-ref name=\"sf-var-debug-ref$ref\">$val</a>";
            } else {
                return "<a class=sf-var-debug-ref href=\"#sf-var-debug-ref$ref\">$val</a>";
            }
        }

        $val = htmlspecialchars($val, ENT_QUOTES, 'UTF-8');

        switch ($style) {
            case 'str':
            case 'public':
                static $cchr = array(
                    "\x1B",
                    "\x00", "\x01", "\x02", "\x03", "\x04", "\x05", "\x06", "\x07",
                    "\x08", "\x09", "\x0A", "\x0B", "\x0C", "\x0D", "\x0E", "\x0F",
                    "\x10", "\x11", "\x12", "\x13", "\x14", "\x15", "\x16", "\x17",
                    "\x18", "\x19", "\x1A", "\x1C", "\x1D", "\x1E", "\x1F", "\x7F",
                );

                foreach ($cchr as $c) {
                    if (false !== strpos($val, $c)) {
                        $r = "\x7F" === $c ? '?' : chr(64 + ord($c));
                        $val = str_replace($c, "<span class=sf-var-debug-cchr>$r</span>", $val);
                    }
                }
        }

        return "<span class=sf-var-debug-$style>$val</span>";
    }

    /**
     * {@inheritdoc}
     */
    protected function dumpLine($depth)
    {
        switch ($this->lastDepth - $depth) {
            case +1: $this->line = '</span>'.$this->line; break;
            case -1: $this->line = "<span class=sf-var-debug-$depth>$this->line"; break;
        }

        if (-1 === $this->lastDepth) {
            if (!$this->headerIsDumped) {
                $this->dumpHeader();
            }
            $this->line = $this->dumpPrefix.$this->line;
        }

        if (false === $depth) {
            $this->lastDepth = -1;
            $this->line .= $this->dumpSuffix;
        } else {
            $this->lastDepth = $depth;
        }

        parent::dumpLine($depth);
    }
}
