<?php
/**
 * @license https://raw.githubusercontent.com/andkirby/commithook/master/LICENSE.md
 */
namespace PreCommit;

/**
 * Class XmlMerger
 *
 * @package PreCommit
 */
class XmlMerger
{
    /**
     * Collection nodes
     *
     * @var array
     */
    protected $collectionNodes = array();

    /**
     * Xpath of a process node
     *
     * @var string
     */
    protected $processXpath = 'root';

    /**
     * Add name of nodes which should a collection
     *
     * @param string $xpath
     */
    public function addCollectionNode($xpath)
    {
        $this->collectionNodes[] = 'root/'.$xpath;
    }

    /**
     * Merge two XML
     *
     * @param \SimpleXMLElement|string $xmlSource
     * @param \SimpleXMLElement|string $xmlUpdate
     * @return \SimpleXMLElement
     */
    public function merge($xmlSource, $xmlUpdate)
    {
        if (is_string($xmlSource)) {
            $xmlSource = simplexml_load_string($xmlSource);
        }
        if (is_string($xmlUpdate)) {
            $xmlUpdate = simplexml_load_string($xmlUpdate);
        }

        $this->makeMerge($xmlSource, $xmlUpdate);

        return $xmlSource;
    }

    /**
     * Merge nodes
     *
     * @param \SimpleXMLElement $xmlSource
     * @param \SimpleXMLElement $xmlUpdate
     * @return $this
     */
    protected function makeMerge($xmlSource, $xmlUpdate)
    {
        /** @var \SimpleXMLElement $node */
        foreach ($xmlUpdate as $name => $node) {
            $this->addProcessXpathName($name);
            /** @var \SimpleXMLElement $nodeSource */
            $nodeSource = $xmlSource->$name;
            if ($this->isCollectionXpath() || !$nodeSource) {
                $this->xmlAppend($xmlSource, $node);
            } else {
                $this->mergeAttributes($nodeSource, $node);

                if ($node->count()) {
                    //merge child nodes
                    $this->makeMerge($nodeSource, $node);
                } else {
                    //set only value
                    $nodeSource[0] = (string) $node;
                }
            }
            $this->unsetProcessXpathName($name);
        }

        return $this;
    }

    /**
     * Add node to process xpath
     *
     * @param string $name
     * @return $this
     */
    protected function addProcessXpathName($name)
    {
        $this->processXpath .= '/'.$name;

        return $this;
    }

    /**
     * Check if such XPath means plenty nodes
     *
     * @return bool
     */
    protected function isCollectionXpath()
    {
        return in_array($this->processXpath, $this->collectionNodes);
    }

    /**
     * Append XML node
     *
     * @param \SimpleXMLElement $to
     * @param \SimpleXMLElement $from
     */
    public function xmlAppend(\SimpleXMLElement $to, \SimpleXMLElement $from)
    {
        $toDom   = dom_import_simplexml($to);
        $fromDom = dom_import_simplexml($from);
        $toDom->appendChild($toDom->ownerDocument->importNode($fromDom, true));
    }

    /**
     * Merge attributes
     *
     * @param \SimpleXMLElement $xmlSource
     * @param \SimpleXMLElement $xmlUpdate
     * @return $this
     */
    protected function mergeAttributes($xmlSource, $xmlUpdate)
    {
        if (!$xmlSource->getName()) {
            return $this;
        }
        $attributes = (array) $xmlSource->attributes();
        $attributes = isset($attributes['@attributes']) ? $attributes['@attributes'] : array();
        foreach ($xmlUpdate->attributes() as $name => $value) {
            if (isset($attributes[$name])) {
                $xmlSource->attributes()->$name = (string) $value;
            } else {
                $xmlSource->addAttribute($name, (string) $value);
            }
        }

        return $this;
    }

    /**
     * Remove node name from process xpath
     *
     * @param string $name
     * @return $this
     */
    protected function unsetProcessXpathName($name)
    {
        $length             = strlen($this->processXpath);
        $lengthName         = strlen($name) + 1;
        $this->processXpath = substr($this->processXpath, 0, $length - $lengthName);

        return $this;
    }
}
