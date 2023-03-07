<?php

namespace Breakdance\Render;

use Breakdance\Lib\Vendor\Twig\Environment;
use Breakdance\Lib\Vendor\Twig\Loader\ArrayLoader;
use Breakdance\Lib\Vendor\Twig\TwigFunction;

use Breakdance\Filesystem\Consts;
use Breakdance\PluginsAPI\PluginsController;
use function Breakdance\Filesystem\HelperFunctions\get_bucket_abs_path;

/* We use a singleton because we want to reuse any template with the same content
Instead of creating a new template each time, which is more expensive

We also get caching with ArrayLoader.
 */

class Twig
{

    use \Breakdance\Singleton;

    /**
     * @var Environment
     */
    private $twig;

    /**
     * @var ArrayLoader
     */
    private $loader;


    // Store Twig's render results by hash
    /**
     * @var string[]
     */
    public $cachedResults = [];

    /**
     * This is a Queue data structure (first-in-first-out)
     * We store up to "$maximumAllowedQueueSize" in the cache and above that, we remove the oldest
     * that way we keep the cache size (aka memory) down. Otherwise it could overflow memory for gigantic pages
     *
     * @var array<array-key, string>
     */
    private $cacheHashQueue = [];
    /**
     * @var int
     */
    private $queuePosition = 0;
    /**
     * I'm deciding on 1000 out of my ass. A test page with 500 caches was ~300kb for me. Even up to 1mb sounds OK.
     * @var int
     */
    private $maximumAllowedQueueSize = 1000;

    private function __construct()
    {
        /*
         * Using ArrayLoader *should* be as performant as the FileLoader
         *
         * The problem with using the ".twig" files is that it would add complexity to our code
         * We also add extra logic to all templates with "breakdance_element_css_template" filter and the macros code below
         * That's only possible if we use template strings. Using files we'd need to add all the code in each file
         */

        // The loader is initialized with basic info and we add the templates strings using the loader's "setTemplate"
        $twigMacros = \Breakdance\Elements\get_twig_macros_string();

        $this->loader = new ArrayLoader([
            'macros.twig' => $twigMacros
        ]);

        $twigCachePath = get_bucket_abs_path(Consts::BREAKDANCE_FS_BUCKET_TWIG_CACHE);
        $this->twig = new Environment($this->loader, ['cache' => $twigCachePath, 'autoescape' => false]);

        foreach (
            PluginsController::getInstance()->TwigFunctionsPHPSide as $twigFunction
        ) {
            $function = new TwigFunction(
                $twigFunction['name'],
                $twigFunction['function'],
                // Added to avoid escaping the double quotes in the cssNames
                ['is_safe' => ['html']]
            );
            $this->twig->addFunction($function);
        }
    }

    /**
     * @param string $template
     * @param PropertiesData $propertiesData
     *
     * @return string
     */
    public function runTwig(string $template, $propertiesData)
    {
        if (!$template) {
            return '';
        }

        // Twig need at least an empty array otherwise it goes BOOM
        if (!$propertiesData) {
            $propertiesData = [];
        }

        $template = "
          {% import \"macros.twig\" as macros %}

          {$template}
         ";

        $templateHash = sha1($template);
        $cacheHash = $templateHash . sha1(json_encode($propertiesData));

        if (isset($this->cachedResults[$cacheHash])) {
            return $this->cachedResults[$cacheHash];
        }

        /**
         * @psalm-suppress MixedMethodCall
         */
        $this->loader->setTemplate($templateHash, $template);

        /**
         * @psalm-suppress MixedMethodCall
         * @psalm-suppress MixedArgument
         */

        if ($this->queuePosition === $this->maximumAllowedQueueSize) {
            $this->queuePosition = 0;
        }

        // Keep the cache size down by removing the oldest element
        if (count($this->cachedResults) > $this->queuePosition) {
            $this->removeOldestFromQueue();
        }

        $this->addToQueue($cacheHash);

        /** @psalm-suppress MixedArgument */
        $this->cachedResults[$cacheHash] = (string)$this->twig->render($templateHash, $propertiesData);

        return $this->cachedResults[$cacheHash];
    }

    private function removeOldestFromQueue()
    {
        /**
         * @var string
         */
        $oldestCacheHash = $this->cacheHashQueue[$this->queuePosition];
        unset($this->cachedResults[$oldestCacheHash]);
    }

    /**
     * @param string $cacheHash
     */
    private function addToQueue($cacheHash)
    {
        $this->cacheHashQueue[$this->queuePosition] = $cacheHash;
        $this->queuePosition++;
    }
}
