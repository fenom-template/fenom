<?php
/*
 * This file is part of Fenom.
 *
 * (c) 2013 Ivan Shalganov
 *
 * For the full copyright and license information, please view the license.md
 * file that was distributed with this source code.
 */
namespace Fenom;

use Fenom;

/**
 * Primitive template
 * @author     Ivan Shalganov <a.cobest@gmail.com>
 */
class Render extends \ArrayObject
{
    private static array $_props = [
        "name"      => "runtime",
        "base_name" => "",
        "scm"       => false,
        "time"      => 0,
        "depends"   => [],
        "macros"    => []
    ];
    /**
     * @var \Closure|null
     */
    protected ?\Closure $_code = null;
    /**
     * Template name
     * @var string
     */
    protected mixed $_name = 'runtime';
    /**
     * Provider's schema
     * @var string|null
     */
    protected ?string $_scm = null;
    /**
     * Basic template name
     * @var string
     */
    protected string $_base_name = 'runtime';
    /**
     * @var Fenom
     */
    protected Fenom $_fenom;
    /**
     * Timestamp of compilation
     * @var float
     */
    protected float $_time = 0.0;

    /**
     * @var array depends list
     */
    protected array $_depends = [];

    /**
     * @var int template options (see Fenom options)
     */
    protected int $_options = 0;

    /**
     * Template provider
     * @var ProviderInterface
     */
    protected ProviderInterface $_provider;

    /**
     * @var \Closure[]
     */
    protected array $_macros;

    /**
     * @param Fenom $fenom
     * @param \Closure $code template body
     * @param array $props
     */
    public function __construct(Fenom $fenom, \Closure $code, array $props = array())
    {
        parent::__construct();
        $this->_fenom = $fenom;
        $props += self::$_props;
        $this->_name      = $props["name"];
        $this->_base_name = $props["base_name"];
        $this->_scm       = $props["scm"];
        $this->_time      = $props["time"];
        $this->_depends   = $props["depends"];
        $this->_macros    = $props["macros"];
        $this->_code      = $code;
    }

    /**
     * Get template storage
     * @return \Fenom
     */
    public function getStorage(): Fenom
    {
        return $this->_fenom;
    }

    /**
     * Get list of dependencies.
     * @return array
     */
    public function getDepends(): array
    {
        return $this->_depends;
    }

    /**
     * Get schema name
     * @return string|null
     */
    public function getScm(): ?string
    {
        return $this->_scm;
    }

    /**
     * Get provider of template source
     * @return ProviderInterface
     */
    public function getProvider(): ProviderInterface
    {
        return $this->_fenom->getProvider($this->_scm);
    }

    /**
     * Get name without schema
     * @return string
     */
    public function getBaseName(): string
    {
        return $this->_base_name;
    }

    /**
     * Get parse options
     * @return int
     */
    public function getOptions(): int
    {
        return $this->_options;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->_name;
    }

    /**
     * Get template name
     * @return string
     */
    public function getName(): string
    {
        return $this->_name;
    }

    public function getTime()
    {
        return $this->_time;
    }


    /**
     * Validate template
     * @return bool
     */
    public function isValid(): bool
    {
        foreach ($this->_depends as $scm => $templates) {
            $provider = $this->_fenom->getProvider($scm);
            if(count($templates) === 1) {
                if ($provider->getLastModified(key($templates)) !== $this->_time) {
                    return false;
                }
            } else {
                if (!$provider->verify($templates)) {
                    return false;
                }
            }
        }
        return true;
    }

    /**
     * Get internal macro
     * @param $name
     * @throws \RuntimeException
     * @return mixed
     */
    public function getMacro($name): mixed
    {
        if (empty($this->_macros[$name])) {
            throw new \RuntimeException('macro ' . $name . ' not found');
        }
        return $this->_macros[$name];
    }

    /**
     * Execute template and write into output
     * @param array $values for template
     * @return array
     */
    public function display(array $values): array
    {
        $this->_code->__invoke($values, $this);
        return $values;
    }

    /**
     * Execute template and return result as string
     * @param array $values for template
     * @return string
     * @throws \Exception
     */
    public function fetch(array $values): string
    {
        ob_start();
        try {
            $this->display($values);
            return ob_get_clean();
        } catch (\Exception $e) {
            ob_end_clean();
            throw $e;
        }
    }

    /**
     * Stub
     * @param string $method
     * @param mixed $args
     * @throws \BadMethodCallException
     */
    public function __call(string $method, mixed $args)
    {
        throw new \BadMethodCallException("Unknown method " . $method);
    }
}
