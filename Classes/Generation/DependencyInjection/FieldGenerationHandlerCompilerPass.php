<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Generation\DependencyInjection;

use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\FieldGenerator;
use SomeBdyElse\Typo3ContentModels\Generation\FieldGeneration\HandlerInterface;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;
use TYPO3\CMS\Core\Service\DependencyOrderingService;
use TYPO3\CMS\Core\Utility\GeneralUtility;

final readonly class FieldGenerationHandlerCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(FieldGenerator::class)) {
            return;
        }

        $handlers = [];
        foreach ($container->findTaggedServiceIds(HandlerInterface::TAG) as $serviceId => $tags) {
            $definition = $container->findDefinition($serviceId);
            if ($definition->isAbstract()) {
                continue;
            }

            $className = $container->getParameterBag()->resolveValue($definition->getClass() ?: $serviceId);
            if (!is_string($className) || !is_a($className, HandlerInterface::class, true)) {
                throw new \InvalidArgumentException(sprintf(
                    'Field generation handler service "%s" must implement %s.',
                    $serviceId,
                    HandlerInterface::class,
                ));
            }

            foreach ($tags as $attributes) {
                $identifier = $this->identifier($serviceId, $attributes['identifier'] ?? null);
                if (isset($handlers[$identifier]) && $handlers[$identifier]['serviceName'] !== $serviceId) {
                    throw new \InvalidArgumentException(sprintf(
                        'Field generation handler identifier "%s" is used by both "%s" and "%s".',
                        $identifier,
                        $handlers[$identifier]['serviceName'],
                        $serviceId,
                    ));
                }

                $handlers[$identifier] = [
                    'before' => $this->normalizeList($attributes['before'] ?? null),
                    'after' => $this->normalizeList($attributes['after'] ?? null),
                    'serviceName' => $serviceId,
                ];
            }
        }

        $orderedHandlers = [];
        foreach ((new DependencyOrderingService())->orderByDependencies($this->expandWildcardDependencies($handlers)) as $handler) {
            $orderedHandlers[] = new Reference($handler['serviceName']);
        }

        $container->findDefinition(FieldGenerator::class)->setArgument('$handlers', $orderedHandlers);
    }

    private function identifier(string $serviceId, mixed $identifier): string
    {
        if ($identifier === null) {
            return $serviceId;
        }
        if (!is_string($identifier) || trim($identifier) === '') {
            throw new \InvalidArgumentException(sprintf(
                'Field generation handler service "%s" must define a non-empty identifier.',
                $serviceId,
            ));
        }

        return $identifier;
    }

    /**
     * @return list<string>
     */
    private function normalizeList(mixed $value): array
    {
        if ($value === null || $value === '') {
            return [];
        }
        if (is_string($value)) {
            return GeneralUtility::trimExplode(',', $value, true);
        }
        if (!is_array($value)) {
            return [];
        }

        $items = [];
        foreach ($value as $item) {
            if (is_string($item) && trim($item) !== '') {
                $items[] = trim($item);
            }
        }

        return $items;
    }

    /**
     * @param array<string, array{before: list<string>, after: list<string>, serviceName: string}> $handlers
     * @return array<string, array{before: list<string>, after: list<string>, serviceName: string}>
     */
    private function expandWildcardDependencies(array $handlers): array
    {
        foreach ($handlers as $identifier => &$handler) {
            if (in_array('*', $handler['before'], true)) {
                $handler['before'] = array_values(array_diff(array_keys($handlers), [$identifier]));
            }
            if (in_array('*', $handler['after'], true)) {
                $handler['after'] = array_values(array_diff(array_keys($handlers), [$identifier]));
            }
        }
        unset($handler);

        return $handlers;
    }
}
