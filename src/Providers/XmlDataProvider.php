<?php

namespace Data\Provider\Providers;

use ArrayObject;
use Closure;
use Exception;
use SimpleXMLElement;
use SimpleXMLIterator;

class XmlDataProvider extends BaseFileDataProvider
{
    /**
     * @var int
     */
    protected $lastPk = 0;

    /**
     * @var string
     */
    private $filePath;
    /**
     * @var string
     */
    private $listNodeName;
    /**
     * @var string
     */
    private $itemNodeName;

    /**
     * @var string
     */
    private $arrayItemDefaultKey;

    public function __construct(
        string $filePath,
        string $listNodeName,
        string $itemNodeName,
        string $pkName = null,
        string $arrayItemDefaultKey = 'arritem'
    ) {
        parent::__construct($pkName);
        $this->filePath = $filePath;
        $this->listNodeName = $listNodeName;
        $this->itemNodeName = $itemNodeName;
        $this->arrayItemDefaultKey = $arrayItemDefaultKey;
    }

    /**
     * @return array[]
     *
     * @throws Exception
     *
     * @psalm-return list<array>
     */
    protected function readDataFromFile(): array
    {
        $resultList = [];
        $rootNode = $this->getListNode($this->getRootNode());
        if (!($rootNode instanceof SimpleXMLIterator)) {
            return [];
        }

        for ($rootNode->rewind(); $rootNode->valid(); $rootNode->next()) {
            $value = $rootNode->current();
            if ($value !== null) {
                $resultList[] = $this->readItem($value);
            }
        }

        return $resultList;
    }

    /**
     * @param array $dataList
     * @return bool
     * @throws Exception
     */
    protected function saveDataList(array $dataList): bool
    {
        $rootNode = $this->getRootNode();
        unset($rootNode->{$this->listNodeName});
        $listNode = $rootNode->addChild($this->listNodeName);

        foreach ($dataList as $data) {
            $item = $listNode->addChild($this->itemNodeName);

            foreach ($data as $key => $value) {
                $this->addItemProp($item, $key, $value);
            }
        }

        return $rootNode->asXML($this->filePath);
    }

    /**
     * @param array $data
     * @return bool
     * @throws Exception
     *
     * @psalm-param ArrayObject|array<array-key, mixed> $data
     */
    protected function appendData($data): bool
    {
        $rootNode = $this->getRootNode();
        $listNode = $this->getListNode($rootNode);
        if (is_null($listNode)) {
            return false;
        }

        $item = $listNode->addChild($this->itemNodeName);
        foreach ($data as $key => $value) {
            $this->addItemProp($item, $key, $value);
        }

        /** @psalm-suppress RedundantCast */
        return (bool) $rootNode->asXML($this->filePath);
    }

    /**
     * @param SimpleXMLIterator $rootNode
     * @return SimpleXMLIterator|SimpleXMLElement|null
     */
    private function getListNode(SimpleXMLIterator $rootNode): ?SimpleXMLElement
    {
        for ($rootNode->rewind(); $rootNode->valid(); $rootNode->next()) {
            if ($rootNode->key() === $this->listNodeName) {
                return $rootNode->current();
            }
        }

        return $rootNode->addChild($this->listNodeName);
    }

    /**
     * @throws Exception
     */
    private function getRootNode(): SimpleXMLIterator
    {
        if (!file_exists($this->filePath)) {
            return new SimpleXMLIterator('<root></root>');
        }

        return new SimpleXMLIterator($this->filePath, 0, true);
    }

    /**
     * @param SimpleXMLIterator $xmlElement
     * @param array $result
     * @return array
     */
    private function readItems(SimpleXMLIterator $xmlElement, array $result = []): array
    {
        for ($xmlElement->rewind(); $xmlElement->valid(); $xmlElement->next()) {
            $result = $this->readItem($xmlElement, $result);
        }

        return $result;
    }

    /**
     * @param SimpleXMLIterator $xmlElement
     * @param array $result
     * @return array
     */
    private function readItem(SimpleXMLIterator $xmlElement, array $result = []): array
    {
        for ($xmlElement->rewind(); $xmlElement->valid(); $xmlElement->next()) {
            $key = $xmlElement->key();
            $xmlElementValue = $xmlElement->current();
            if (empty($xmlElementValue)) {
                continue;
            }

            if (!array_key_exists($key, $result)) {
                if ($xmlElement->hasChildren()) {
                    if ($key === $this->arrayItemDefaultKey) {
                        $result[] = $this->readItems($xmlElementValue);
                    } else {
                        $result[$key] = $this->readItems($xmlElementValue);
                    }
                } else {
                    if ($key === $this->arrayItemDefaultKey) {
                        $result[] = strval($xmlElementValue);
                    } else {
                        $result[$key] = strval($xmlElementValue);
                    }
                }
            }
        }

        return $result;
    }

    /**
     * @return string
     */
    public function getSourceName(): string
    {
        return $this->filePath;
    }

    /**
     * @return null
     */
    public function getDataHandler(): ?Closure
    {
        return null;
    }

    /**
     * @param SimpleXMLElement $element
     * @param string $key
     * @param mixed $value
     *
     * @return void
     */
    public function addItemProp(SimpleXMLElement $element, string $key, $value)
    {
        if (empty($value)) {
            return;
        }

        if (!is_array($value)) {
            $element->addChild($key, $value);
            return;
        }

        $prop = $element->addChild($key);
        foreach ($value as $k => $v) {
            if (empty($v)) {
                continue;
            }

            if (is_int($k)) {
                $k = $this->arrayItemDefaultKey;
            }

            $this->addItemProp($prop, $k, $v);
        }
    }

    /**
     * @return bool
     */
    public function startTransaction(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function commitTransaction(): bool
    {
        return false;
    }

    /**
     * @return bool
     */
    public function rollbackTransaction(): bool
    {
        return false;
    }
}
