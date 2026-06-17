<?php

declare(strict_types=1);

namespace SomeBdyElse\Typo3ContentModels\Rendering\DependencyInjection;

use SomeBdyElse\Typo3ContentModels\Contract\ContentModel;
use SomeBdyElse\Typo3ContentModels\Contract\ContentModelInterface;
use SomeBdyElse\Typo3ContentModels\Rendering\ContentModelRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;

final class ContentModelCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        if (!$container->hasDefinition(ContentModelRegistry::class)) {
            return;
        }

        $contentModelsByTableAndType = [];
        foreach ($container->findTaggedResourceIds(ContentModel::TAG) as $serviceId => $tags) {
            $definition = $container->findDefinition($serviceId);
            $className = $definition->getClass() ?: $serviceId;
            if (!is_string($className) || !is_a($className, ContentModelInterface::class, true)) {
                continue;
            }

            foreach ($tags as $tag) {
                $table = $tag['table'] ?? null;
                if (!is_string($table) || $table === '') {
                    throw new \InvalidArgumentException(sprintf(
                        'Content model "%s" must define a non-empty table.',
                        $serviceId,
                    ));
                }

                $type = $tag['type'] ?? null;
                if ($type !== null && !is_string($type)) {
                    $type = (string) $type;
                }

                $contentModelsByTableAndType[$table][(string) $type] = $className;
            }
        }

        $registryDefinition = $container->findDefinition(ContentModelRegistry::class);
        foreach ($contentModelsByTableAndType as $table => $contentModelsByType) {
            foreach ($contentModelsByType as $type => $className) {
                $registryDefinition->addMethodCall('registerContentModel', [$table, $type, $className]);
            }
        }
    }
}
