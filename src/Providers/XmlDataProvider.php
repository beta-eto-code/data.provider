<?php

namespace Data\Provider\Providers;

use Closure;
use Data\Provider\Interfaces\DataProviderInterface;
use Data\Provider\Interfaces\OperationResultInterface;
use Data\Provider\Interfaces\QueryCriteriaInterface;
use Data\Provider\OperationResult;
use Exception;
use SimpleXMLElement;
use SimpleXMLIterator;

class XmlDataProvider implements DataProviderInterface
{
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
     * @var Closure|string
     */
    private $pkKey;

    public function __construct(string $filePath, string $listNodeName, string $itemNodeName, $pkKey)
    {
        $this->filePath = $filePath;
        $this->listNodeName = $listNodeName;
        $this->itemNodeName = $itemNodeName;
        $this->pkKey = $pkKey;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     * @throws Exception
     */
    public function getData(QueryCriteriaInterface $query): array
    {
        $data = $this->getDataInternal($query);
        foreach ($query->getJoinList() as $joinRule) {
            $joinRule->loadTo($data);
            $joinRule->filterData($data);
        }

        return $data;
    }

    /**
     * @param SimpleXMLIterator $rootNode
     * @return SimpleXMLIterator|null
     */
    private function getListNode(SimpleXMLIterator $rootNode): ?SimpleXMLIterator
    {
        for($rootNode->rewind(); $rootNode->valid(); $rootNode->next()) {
            if ($rootNode->key() === $this->listNodeName) {
                return $rootNode->current();
            }
        }

        return null;
    }

    /**
     * @return SimpleXMLIterator|null
     * @throws Exception
     */
    private function getRootNode(): ?SimpleXMLIterator
    {
        return new SimpleXMLIterator($this->filePath, null, true);
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return array
     * @throws Exception
     */
    protected function getDataInternal(QueryCriteriaInterface $query): array
    {
        $rootNode = $this->getListNode($this->getRootNode());
        if (!($rootNode instanceof SimpleXMLIterator)) {
            return [];
        }

        $resultList = [];
        $limit = $query->getLimit();
        $offset = $query->getOffset();
        $criteriaList = $query->getCriteriaList();
        for ($rootNode->rewind(); $rootNode->valid(); $rootNode->next()) {
            if ($offset > 0) {
                $offset--;
                continue;
            }

            $item = $this->readItem($rootNode->current());
            $isSuccess = true;
            foreach ($criteriaList as $compareRule) {
                if (!$compareRule->assertWithData($item)) {
                    $isSuccess = false;
                    break;
                }
            }

            if ($isSuccess) {
                $resultList[] = $item;
                if ($limit > 0 && count($resultList) >= $limit) {
                    break;
                }
            }
        }

        return $resultList;
    }

    /**
     * @param SimpleXMLIterator $xmlElement
     * @param array $result
     * @return array
     */
    private function readItems(SimpleXMLIterator $xmlElement, array $result = []): array
    {
        for($xmlElement->rewind(); $xmlElement->valid(); $xmlElement->next()) {
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
        if(!array_key_exists($xmlElement->key(), $result)){
            $result[$xmlElement->key()] = array();
            if($xmlElement->hasChildren()){
                $result[$xmlElement->key()][] = $this->readItems($xmlElement->current());
            } else{
                $result[$xmlElement->key()][] = strval($xmlElement->current());
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
     * @return Closure|null
     */
    public function getDataHandler(): ?Closure
    {
        return null;
    }

    /**
     * @param QueryCriteriaInterface $query
     * @return int
     * @throws Exception
     */
    public function getDataCount(QueryCriteriaInterface $query): int
    {
        return count($this->getData($query));
    }

    /**
     * @param SimpleXMLIterator $listNode
     * @param $pk
     * @return SimpleXMLElement|null
     */
    private function findItemByPk(SimpleXMLIterator $listNode, $pk): ?SimpleXMLElement
    {
        for($listNode->rewind(); $listNode->valid(); $listNode->next()) {
            $item = $listNode->current();
            $itemData = $this->readItem($item);
            if (is_callable($this->pkKey) && !($this->pkKey)($itemData, $pk)) {
                continue;
            }

            if (!is_callable($this->pkKey) && ($itemData[$pk] ?? null) != $pk) {
                continue;
            }

            return $item;
        }

        return null;
    }

    /**
     * @param array $data
     * @param mixed|null $pk
     * @return OperationResultInterface
     * @throws Exception
     */
    public function save(array $data, $pk = null): OperationResultInterface
    {
        $rootNode = $this->getRootNode();
        $listNode = $this->getListNode($rootNode);
        if (!($listNode instanceof SimpleXMLIterator)) {
            $listNode = $rootNode->addChild($this->listNodeName);
        }

        if (empty($pk) || !$listNode->hasChildren()) {
            $item = $listNode->addChild($this->itemNodeName);
            foreach ($data as $key => $value) {
                $this->addItemProp($item, $key, $value);
            }

            $isSuccess = (bool)$rootNode->asXML($this->filePath);
            return $isSuccess ?
                new OperationResult() :
                new OperationResult('Ошибка сохранения данных', ['pk' => $pk, 'data' => $data]);
        }

        $item = $this->findItemByPk($listNode, $pk);
        if (!($item instanceof SimpleXMLIterator)) {
            return new OperationResult('Элемент не найден', ['pk' => $pk, 'data' => $data]);
        }

        foreach ($data as $key => $value) {
            $this->updateItemProp($item, $key, $value);
        }

        $isSuccess = (bool)$rootNode->asXML($this->filePath);
        return $isSuccess ?
            new OperationResult() :
            new OperationResult('Ошибка сохранения данных', ['pk' => $pk, 'data' => $data]);
    }

    public function addItemProp(SimpleXMLElement $element, string $key, $value)
    {
        if (!is_array($value)) {
            $element->addChild($key, $value);
            return;
        }

        $prop = $element->addChild($key);
        foreach ($value as $k => $v) {
            $this->addItemProp($prop, $k, $v);
        }
    }

    /**
     * @param SimpleXMLIterator $element
     * @param string $key
     * @param $value
     */
    public function updateItemProp(SimpleXMLIterator $element, string $key, $value)
    {
        for($element->rewind(); $element->valid(); $element->next()) {
            if ($element->key() !== $key) {
                continue;
            }

            if (!is_array($value)) {
                $element->current()[0] = $value;
                return;
            }

            foreach ($value as $k => $v) {
                $this->updateItemProp($element->current(), $k, $v);
                return;
            }
        }

        $this->addItemProp($element, $key, $value);
    }

    /**
     * @param mixed $pk
     * @return OperationResultInterface
     * @throws Exception
     */
    public function remove($pk): OperationResultInterface
    {
        $rootNode = $this->getRootNode();
        $listNode = $this->getListNode($rootNode);
        $item = $this->findItemByPk($listNode, $pk);
        if (!($item instanceof SimpleXMLIterator)) {
            return new OperationResult('Элемент не найден', ['pk' => $pk]);
        }

        $dom = dom_import_simplexml($item);
        $dom->parentNode->removeChild($dom);

        $isSuccess = (bool)$rootNode->asXML($this->filePath);
        return $isSuccess ?
            new OperationResult() :
            new OperationResult('Ошибка удаления данных', ['pk' => $pk]);
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