<?php

/**
 * Copyright 2017 Cloud Creativity Limited
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CloudCreativity\LaravelJsonApi\Routing;

use Generator;
use CloudCreativity\LaravelJsonApi\Utils\Environment;
use Illuminate\Contracts\Routing\Registrar;
use Illuminate\Routing\Route;
use Illuminate\Support\Fluent;

/**
 * Class RelationshipsGroup
 *
 * @package CloudCreativity\LaravelJsonApi
 */
class RelationshipsGroup
{

    use RegistersResources;

    /**
     * RelationshipsGroup constructor.
     *
     * @param $resourceType
     * @param Fluent $options
     */
    public function __construct($resourceType, Fluent $options)
    {
        $this->resourceType = $resourceType;
        $this->options = $options;
    }

    /**
     * @param Registrar $router
     */
    public function addRelationships($router)
    {
        foreach ($this->relationships() as $relationship => $options) {
            foreach ($options['actions'] as $action) {
                $this->relationshipRoute($router, $relationship, $action);
            }
        }
    }

    /**
     * @param Registrar $router
     * @param $relationship
     * @param $action
     * @return Route
     */
    protected function relationshipRoute($router, $relationship, $action)
    {
        $route = $this->createRoute(
            $router,
            $this->routeMethod($action),
            $this->routeUrl($relationship, $action),
            $this->routeAction($relationship, $action)
        );

        if (Environment::isLaravel()) {
            // ToDo: Set relationship in Lumen
            $route->defaults(ResourceRegistrar::PARAM_RELATIONSHIP_NAME, $relationship);

            return $route;
        }
    }

    /**
     * @return Generator
     */
    protected function relationships()
    {
        foreach ($this->hasOne() as $hasOne => $options) {
            $options['actions'] = $this->hasOneActions($options);
            yield $hasOne => $options;
        }

        foreach ($this->hasMany() as $hasMany => $options) {
            $options['actions'] = $this->hasManyActions($options);
            yield $hasMany => $options;
        }
    }

    /**
     * @param array $options
     * @return array
     */
    protected function hasOneActions(array $options)
    {
        return $this->diffActions(['related', 'read', 'replace'], $options);
    }

    /**
     * @param array $options
     * @return array
     */
    protected function hasManyActions(array $options)
    {
        return $this->diffActions(['related', 'read', 'replace', 'add', 'remove'], $options);
    }

    /**
     * @param $action
     * @return string
     */
    protected function routeMethod($action)
    {
        $methods = [
            'related' => 'get',
            'read' => 'get',
            'replace' => 'patch',
            'add' => 'post',
            'remove' => 'delete',
        ];

        return $methods[$action];
    }

    /**
     * @param $relationship
     * @param $action
     * @return string
     */
    protected function routeUrl($relationship, $action)
    {
        if ('related' === $action) {
            return $this->relatedUrl($relationship);
        }

        return $this->relationshipUrl($relationship);
    }

    /**
     * @param $relationship
     * @param $action
     * @return array
     */
    protected function routeAction($relationship, $action)
    {
        return [
            'as' => $this->routeName($relationship, $action),
            'uses' => $this->controllerAction($action),
        ];
    }

    /**
     * @param $relationship
     * @param $action
     * @return string
     */
    protected function routeName($relationship, $action)
    {
        $name = "relationships.{$relationship}";

        if ('related' !== $action) {
            $name .= ".{$action}";
        }

        return $name;
    }

    /**
     * @param $action
     * @return string
     */
    protected function controllerAction($action)
    {
        $methods = [
            'related' => 'readRelatedResource',
            'read' => 'readRelationship',
            'replace' => 'replaceRelationship',
            'add' => 'addToRelationship',
            'remove' => 'removeFromRelationship',
        ];

        return sprintf('%s@%s', $this->controller(), $methods[$action]);
    }
}
