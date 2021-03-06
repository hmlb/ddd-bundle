<?php

declare (strict_types = 1);

namespace HMLB\DDDBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Processor;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Extension\PrependExtensionInterface;
use Symfony\Component\DependencyInjection\Loader;
use Symfony\Component\HttpKernel\DependencyInjection\Extension;

/**
 * HMLBDDDExtension.
 *
 * @author Hugues Maignol <hugues@hmlb.fr>
 */
class HMLBDDDExtension extends Extension implements PrependExtensionInterface
{
    /**
     * We add mapping information for our Messages Classes.
     *
     * todo: It should be dynamic for non default entity_manager name
     */
    public function prepend(ContainerBuilder $container)
    {
        $bundles = $container->getParameter('kernel.bundles');

        if (isset($bundles['DoctrineBundle'])) {
            $mappingConfig = [
                'orm' => [
                    'entity_managers' => [
                        'default' => [
                            'mappings' => [
                                'HMLBDDDBundle' => [
                                    'mapping' => true,
                                    'type' => 'xml',
                                    'dir' => __DIR__.'/../Resources/config/doctrine',
                                    'prefix' => 'HMLB\DDD',
                                    'is_bundle' => false,
                                ],
                            ],
                        ],
                    ],
                ],
            ];

            $container->getExtension('doctrine');
            $container->prependExtensionConfig('doctrine', $mappingConfig);
        }

        $container->prependExtensionConfig(
            'command_bus',
            [
                'command_name_resolver_strategy' => 'named_message',
            ]
        );
        $container->prependExtensionConfig(
            'event_bus',
            [
                'event_name_resolver_strategy' => 'named_message',
            ]
        );
    }

    public function load(array $configs, ContainerBuilder $container): array
    {
        $processor = new Processor();
        $configuration = new Configuration();
        $config = $processor->processConfiguration($configuration, $configs);

        $loader = new Loader\XmlFileLoader($container, new FileLocator(__DIR__.'/../Resources/config'));
        $loader->load('services.xml');

        if ($config['db_driver']) {
            $loader->load(sprintf('%s.xml', $config['db_driver']));
            $container->setParameter($this->getAlias().'.backend_type_'.$config['db_driver'], true);
        }

        foreach (['messages'] as $basename) {
            $loader->load(sprintf('%s.xml', $basename));
        }

        $container->setAlias('hmlb_ddd.repository.command', $config['persistence']['command_repository_service']);
        $container->setAlias('hmlb_ddd.repository.event', $config['persistence']['event_repository_service']);

        $this->remapParametersNamespaces(
            $config,
            $container,
            [
                '' => [
                    'db_driver' => 'hmlb_ddd.db_driver',
                ],
                'persistence' => [
                    'persist_commands' => 'hmlb_ddd.persistence.persist_commands',
                    'persist_events' => 'hmlb_ddd.persistence.persist_events',
                    'command_repository_service' => 'hmlb_ddd.persistence.command_repository_service',
                    'event_repository_service' => 'hmlb_ddd.persistence.event_repository_service',
                ],
            ]
        );

        return $config;
    }

    private function remapParameters(array $config, ContainerBuilder $container, array $map)
    {
        foreach ($map as $name => $paramName) {
            if (array_key_exists($name, $config)) {
                $container->setParameter($paramName, $config[$name]);
            }
        }
    }

    private function remapParametersNamespaces(array $config, ContainerBuilder $container, array $namespaces)
    {
        foreach ($namespaces as $ns => $map) {
            if ($ns) {
                if (!array_key_exists($ns, $config)) {
                    continue;
                }
                $namespaceConfig = $config[$ns];
            } else {
                $namespaceConfig = $config;
            }
            if (is_array($map)) {
                $this->remapParameters($namespaceConfig, $container, $map);
            } else {
                foreach ($namespaceConfig as $name => $value) {
                    $container->setParameter(sprintf($map, $name), $value);
                }
            }
        }
    }

    public function getAlias(): string
    {
        return 'hmlb_ddd';
    }
}
