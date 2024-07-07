<?php
 

namespace Modules\Core\App\Support\HtmlPurifier;

use Exception;
use HTMLPurifier;
use HTMLPurifier_AttrDef_CSS_Percentage;
use HTMLPurifier_AttrDef_Enum;
use HTMLPurifier_HTML5Config;
use HTMLPurifier_HTMLDefinition;
use Illuminate\Contracts\Config\Repository;
use Illuminate\Filesystem\Filesystem;

/**
 * @codeCoverageIgnore
 */
class Purifier
{
    /**
     * @var \HTMLPurifier
     */
    protected $purifier;

    /**
     * Initialize new Purifier instance.
     */
    public function __construct(protected Filesystem $files, protected Repository $config)
    {
        $this->setUp();
    }

    /**
     * Setup
     *
     * @throws Exception
     */
    private function setUp()
    {
        $this->checkCacheDirectory();

        // Create a new configuration object
        $config = $this->getConfig();

        if ($this->config->get('html_purifier.flex')) {
            $this->addCssFlexSupport($config->getDefinition('CSS'));
        }

        // Create HTMLPurifier object
        $this->purifier = new HTMLPurifier($config);
    }

    /**
     * Add a custom definition
     *
     * @see http://htmlpurifier.org/docs/enduser-customize.html
     *
     * @param  \HTMLPurifier_HTML5Config  $configObject  Defaults to using default config
     * @return \HTMLPurifier_HTML5Config $configObject
     */
    private function addCustomDefinition(array $definitionConfig, ?HTMLPurifier_HTML5Config $configObject = null)
    {
        if (! $configObject) {
            $configObject = HTMLPurifier_HTML5Config::createDefault();
            $configObject->loadArray($this->getConfig());
        }

        // Setup the custom definition
        $configObject->set('HTML.DefinitionID', $definitionConfig['id']);
        $configObject->set('HTML.DefinitionRev', $definitionConfig['rev']);

        // Enable debug mode
        if (! isset($definitionConfig['debug']) || $definitionConfig['debug']) {
            $configObject->set('Cache.DefinitionImpl', null);
        }

        // Start configuring the definition
        if ($def = $configObject->maybeGetRawHTMLDefinition()) {
            // Create the definition attributes
            if (! empty($definitionConfig['attributes'])) {
                $this->addCustomAttributes($definitionConfig['attributes'], $def);
            }

            // Create the definition elements
            if (! empty($definitionConfig['elements'])) {
                $this->addCustomElements($definitionConfig['elements'], $def);
            }
        }

        return $configObject;
    }

    /**
     * Add provided attributes to the provided definition
     *
     *
     * @return \HTMLPurifier_HTMLDefinition $definition
     */
    private function addCustomAttributes(array $attributes, HTMLPurifier_HTMLDefinition $definition)
    {
        foreach ($attributes as $attribute) {
            // Get configuration of attribute
            $required = ! empty($attribute[3]) ? true : false;
            $onElement = $attribute[0];
            $attrName = $required ? $attribute[1].'*' : $attribute[1];
            $validValues = $attribute[2];

            $definition->addAttribute($onElement, $attrName, $validValues);
        }

        return $definition;
    }

    /**
     * Add provided elements to the provided definition
     *
     *
     * @return \HTMLPurifier_HTMLDefinition $definition
     */
    private function addCustomElements(array $elements, HTMLPurifier_HTMLDefinition $definition)
    {
        foreach ($elements as $element) {
            // Get configuration of element
            $name = $element[0];
            $contentSet = $element[1];
            $allowedChildren = $element[2];
            $attributeCollection = $element[3];
            $attributes = isset($element[4]) ? $element[4] : null;

            if (! empty($attributes)) {
                $definition->addElement($name, $contentSet, $allowedChildren, $attributeCollection, $attributes);
            } else {
                $definition->addElement($name, $contentSet, $allowedChildren, $attributeCollection);
            }
        }
    }

    /**
     * Check/Create cache directory
     */
    private function checkCacheDirectory()
    {
        $cachePath = $this->config->get('html_purifier.cachePath');

        if ($cachePath && ! $this->files->isDirectory($cachePath)) {
            $this->files->makeDirectory($cachePath, $this->config->get('html_purifier.cacheFileMode', 0755), true);
        }
    }

    /**
     * @return mixed|null
     */
    protected function getConfig($config = null)
    {
        // Create a new configuration object
        // https://github.com/xemlock/htmlpurifier-html5/issues/28
        $configObject = HTMLPurifier_HTML5Config::create(
            HTMLPurifier_HTML5Config::createDefault()
        );

        // Allow configuration to be modified
        if (! $this->config->get('html_purifier.finalize')) {
            $configObject->autoFinalize = false;
        }

        // Set default config
        $defaultConfig = [];
        $defaultConfig['Core.Encoding'] = $this->config->get('html_purifier.encoding');
        $defaultConfig['Cache.SerializerPath'] = $this->config->get('html_purifier.cachePath');
        $defaultConfig['Cache.SerializerPermissions'] = $this->config->get('html_purifier.cacheFileMode', 0755);

        if (! $config) {
            $config = $this->config->get('html_purifier.settings.default');
        } elseif (is_string($config)) {
            $config = $this->config->get('html_purifier.settings.'.$config);
        }

        if (! is_array($config)) {
            $config = [];
        }

        // Merge configurations
        $config = $defaultConfig + $config;

        // Load to Purifier config
        $configObject->loadArray($config);

        // Load custom definition if set
        if ($definitionConfig = $this->config->get('html_purifier.settings.custom_definition')) {
            $this->addCustomDefinition($definitionConfig, $configObject);
        }

        // Load custom elements if set
        if ($elements = $this->config->get('html_purifier.settings.custom_elements')) {
            if ($def = $configObject->maybeGetRawHTMLDefinition()) {
                $this->addCustomElements($elements, $def);
            }
        }

        // Load custom attributes if set
        if ($attributes = $this->config->get('html_purifier.settings.custom_attributes')) {
            if ($def = $configObject->maybeGetRawHTMLDefinition()) {
                $this->addCustomAttributes($attributes, $def);
            }
        }

        return $configObject;
    }

    /**
     * @return mixed
     */
    public function clean($dirty, $config = null, ?\Closure $postCreateConfigHook = null)
    {
        if (! $this->config->get('html_purifier.enabled')) {
            return $dirty;
        }

        if (is_array($dirty)) {
            return array_map(fn ($item) => $this->clean($item, $config), $dirty);
        }

        $configObject = null;
        if ($config !== null) {
            $configObject = $this->getConfig($config);

            if ($postCreateConfigHook !== null) {
                $postCreateConfigHook->call($this, $configObject);
            }
        }

        return $this->purifier->purify($dirty, $configObject);
    }

    /**
     * Add flex attributes to the given CSS definition
     *
     * @param  \HTMLPurifier_CSSDefinition  $definition
     * @return void
     */
    public function addCssFlexSupport($definition)
    {
        $definition->info['display'] = new HTMLPurifier_AttrDef_Enum([
            'inline',
            'block',
            'list-item',
            'run-in',
            'compact',
            'marker',
            'table',
            'inline-block',
            'inline-table',
            'table-row-group',
            'table-header-group',
            'table-footer-group',
            'table-row',
            'table-column-group',
            'table-column',
            'table-cell',
            'table-caption',
            'none',
            'flex',
        ]);

        $definition->info['flex-direction'] = new HTMLPurifier_AttrDef_Enum([
            'column',
            'column-reverse',
            'row',
            'row-reverse',
        ]);

        $definition->info['flex-wrap'] = new HTMLPurifier_AttrDef_Enum([
            'wrap',
            'nowrap',
            'wrap-reverse',
        ]);

        $definition->info['justify-content'] = new HTMLPurifier_AttrDef_Enum([
            'center',
            'flex-start',
            'flex-end',
            'space-around',
            'space-between',
        ]);

        $definition->info['align-items'] = new HTMLPurifier_AttrDef_Enum([
            'center',
            'flex-start',
            'flex-end',
            'stretch',
            'baseline',
        ]);

        $definition->info['align-content'] = new HTMLPurifier_AttrDef_Enum([
            'space-between',
            'space-around',
            'stretch',
            'center',
            'flex-start',
            'flex-end',
        ]);

        $definition->info['flex-basis'] = new HTMLPurifier_AttrDef_CSS_Percentage();

        $definition->info['flex'] = new HTMLPurifier_AttrDef_CSS_Flex();
    }

    /**
     * Get HTMLPurifier instance.
     *
     * @return \HTMLPurifier
     */
    public function getInstance()
    {
        return $this->purifier;
    }
}
