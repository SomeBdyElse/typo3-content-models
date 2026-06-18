<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModelFactoryInterface;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use TYPO3\CMS\Core\Collection\LazyRecordCollection;
use TYPO3\CMS\Core\Domain\Record;
use TYPO3\CMS\Core\Domain\RecordInterface;
use TYPO3\CMS\Core\Utility\GeneralUtility;

/**
 * @template TItem of ContentModelInterface|Record
 * @implements \IteratorAggregate<int|string, TItem>
 * @implements \ArrayAccess<int|string, TItem|null>
 */
final class LazyContentModelCollection implements \IteratorAggregate, \ArrayAccess, \Countable
{
    /**
     * @var array<int|string, TItem>
     */
    private array $items = [];

    /**
     * @param class-string<TItem>|list<class-string<TItem>> $itemClassNames
     */
    public function __construct(
        private readonly LazyRecordCollection $records,
        private readonly string|array $itemClassNames = Record::class,
        private ?ContentModelFactoryInterface $contentModelFactory = null,
    ) {
    }

    /**
     * @template TFromRecordsItem of ContentModelInterface|Record
     * @param class-string<TFromRecordsItem>|list<class-string<TFromRecordsItem>> $itemClassNames
     * @return self<TFromRecordsItem>
     */
    public static function fromRecords(
        LazyRecordCollection $records,
        string|array $itemClassNames = Record::class,
    ): self {
        return new self(
            $records,
            $itemClassNames,
        );
    }

    public function records(): LazyRecordCollection
    {
        return $this->records;
    }

    public function count(): int
    {
        return count($this->records);
    }

    /**
     * @return \Traversable<int|string, TItem>
     */
    public function getIterator(): \Traversable
    {
        foreach ($this->records as $offset => $record) {
            yield $offset => $this->itemForRecord($offset, $record);
        }
    }

    public function __toString(): string
    {
        return (string)$this->records;
    }

    public function offsetExists(mixed $offset): bool
    {
        return $this->records->offsetExists($offset);
    }

    /**
     * @return TItem|null
     */
    public function offsetGet(mixed $offset): ContentModelInterface|Record|null
    {
        $record = $this->records->offsetGet($offset);
        if ($record === null) {
            return null;
        }

        return $this->itemForRecord($offset, $record);
    }

    public function offsetSet(mixed $offset, mixed $value): void
    {
        throw new \RuntimeException('Modifying the content model collection is not implemented.', 1760000001);
    }

    public function offsetUnset(mixed $offset): void
    {
        throw new \RuntimeException('Removing items from the content model collection is not implemented.', 1760000002);
    }

    /**
     * @return list<TItem>
     */
    public function toArray(): array
    {
        return array_values(iterator_to_array($this));
    }

    /**
     * @return TItem
     */
    private function itemForRecord(int|string $offset, RecordInterface $record): ContentModelInterface|Record
    {
        if (isset($this->items[$offset])) {
            return $this->items[$offset];
        }
        if (!$record instanceof Record) {
            throw new \UnexpectedValueException(sprintf(
                'Expected related record to be an instance of %s, got %s.',
                Record::class,
                get_debug_type($record),
            ), 1760000003);
        }

        $itemClassNames = is_array($this->itemClassNames) ? $this->itemClassNames : [$this->itemClassNames];
        if ($this->onlyAllowsRecords($itemClassNames)) {
            return $this->items[$offset] = $record;
        }

        $model = ($this->contentModelFactory())($record);
        foreach ($itemClassNames as $itemClassName) {
            if ($itemClassName === Record::class) {
                continue;
            }
            if (is_a($model, $itemClassName)) {
                return $this->items[$offset] = $model;
            }
        }

        if (in_array(Record::class, $itemClassNames, true)) {
            return $this->items[$offset] = $record;
        }

        throw new \UnexpectedValueException(sprintf(
            'Expected related item to be an instance of %s, got %s.',
            implode('|', $itemClassNames === [] ? [Record::class] : $itemClassNames),
            get_debug_type($model),
        ), 1760000004);
    }

    /**
     * @param list<class-string<TItem>> $itemClassNames
     */
    private function onlyAllowsRecords(array $itemClassNames): bool
    {
        return $itemClassNames === [] || $itemClassNames === [Record::class];
    }

    private function contentModelFactory(): ContentModelFactoryInterface
    {
        return $this->contentModelFactory ??= GeneralUtility::getContainer()->get(ContentModelFactoryInterface::class);
    }
}
