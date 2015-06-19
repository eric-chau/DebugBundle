<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee. If not, see <http://www.gnu.org/licenses/>.
 *
 * @author Charles Rouillon <charles.rouillon@lp-digital.fr>
 */

namespace BackBee\Bundle\DebugBundle\Command;

use BackBee\Console\AbstractCommand;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Routing\RouteCollection;
use Symfony\Component\Routing\Route;

/**
 * A console command for retrieving information about routes.
 *
 * A (proud) adaptation of Symfony framework's one
 *
 * @author  Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class DebugRoutingCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:routing')
            ->setDefinition(array(
                new InputArgument('name', InputArgument::OPTIONAL, 'A route name'),
                new InputOption('show-controllers', null, InputOption::VALUE_NONE, 'Show assigned controllers in overview'),
            ))
            ->setDescription('Displays current routes for an application')
            ->setHelp(<<<EOF
The <info>%command.name%</info> displays the configured routes:

  <info>php %command.full_name%</info>

EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \InvalidArgumentException When route does not exist
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $headers = array('Name', 'Method', 'Scheme', 'Host', 'Path');
        $showControllers = $input->getOption('show-controllers');

        $table = new Table($output);
        $table->setHeaders($showControllers ? array_merge($headers, array('Controller')) : $headers);

        if ($name = $input->getArgument('name')) {
            $route = $this->getContainer()->get('routing')->get($name);
            if (!$route) {
                throw new \InvalidArgumentException(sprintf('The route "%s" does not exist.', $name));
            }

            return $output->write($this->describeRoute($route, $name, $showControllers));
        } else {
            $routes = $this->getContainer()->get('routing');
            $table = $this->describeRouteCollection($table, $routes, $showControllers);
        }
        $table->render();
    }

    /**
     * Returns a more readable controller/action description for a Route
     * @param  Route  $route The selected Route
     * @return string        The couple Controller::Action if available
     */
    private function convertController(Route $route)
    {
        if ($route->hasDefault('_controller') && $route->hasDefault('_action')) {
            return $route->getDefault('_controller').'::'.$route->getDefault('_action');
        }

        throw new \InvalidArgumentException(sprintf('The route with path "%s" does not have defaults _controller or _action.', $route->getPath()));
    }

    /**
     * Returns a descriptive string for a RouteCollection
     * @param  Table           $table           The Symfony Console Table Helper
     * @param  RouteCollection $routes          A collection of routes
     * @param  bool            $showControllers If true, display Controller informations
     * @return string                           The text description of a RouteCollection
     */
    private function describeRouteCollection(Table $table, RouteCollection $routes, $showControllers)
    {
        foreach ($routes->all() as $name => $route) {
            $row = array(
                $name,
                $route->getMethods() ? implode('|', $route->getMethods()) : 'ANY',
                $route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY',
                '' !== $route->getHost() ? $route->getHost() : 'ANY',
                $route->getPath(),
            );
            if ($showControllers) {
                $controller = $route->getDefault('_controller');
                if ($controller instanceof \Closure) {
                    $controller = 'Closure';
                } elseif (is_object($controller)) {
                    $controller = get_class($controller);
                }
                $row[] = $controller;
            }
            $table->addRow($row);
        }

        return $table;
    }

    /**
     * Returns a descriptive string for a Route
     * @param  Route  $route           A BackBee Route instance
     * @param  string $routeName       The Route name (the associated index in a RouteCollection)
     * @param  bool   $showControllers If true, display the Controller information
     * @return string                  The text description of a Route
     */
    private function describeRoute(Route $route, $routeName, $showControllers)
    {
        $description = array(
            '<comment>Name</comment>         '.$routeName,
            '<comment>Path</comment>         '.$route->getPath(),
            '<comment>Path Regex</comment>   '.$route->compile()->getRegex(),
            '<comment>Host</comment>         '.('' !== $route->getHost() ? $route->getHost() : 'ANY'),
            '<comment>Host Regex</comment>   '.('' !== $route->getHost() ? $route->compile()->getHostRegex() : ''),
            '<comment>Scheme</comment>       '.($route->getSchemes() ? implode('|', $route->getSchemes()) : 'ANY'),
            '<comment>Method</comment>       '.($route->getMethods() ? implode('|', $route->getMethods()) : 'ANY'),
            '<comment>Class</comment>        '.get_class($route),
            '<comment>Defaults</comment>     '.$this->formatRouterConfig($route->getDefaults()),
            '<comment>Requirements</comment> '.($route->getRequirements() ? $this->formatRouterConfig($route->getRequirements()) : 'NO CUSTOM'),
            '<comment>Options</comment>      '.$this->formatRouterConfig($route->getOptions()),
        );
        if (isset($showControllers)) {
            array_unshift($description, '<comment>Controller</comment>   '.$this->convertController($route));
        }

        return implode("\n", $description)."\n";
    }

    /**
     * Returns a descriptive string from Route options/requirements
     *
     * @param array $routeOptions
     *
     * @return string
     */
    private function formatRouterConfig(array $routeOptions)
    {
        if (!count($routeOptions)) {
            return 'NONE';
        }
        $string = '';
        ksort($routeOptions);
        foreach ($routeOptions as $name => $value) {
            $string .= ($string ? "\n".str_repeat(' ', 13) : '').$name.': '.$this->formatValue($value);
        }

        return $string;
    }

    /**
     * Formats a value as string.
     *
     * @param mixed $value
     *
     * @return string
     */
    protected function formatValue($value)
    {
        if (is_object($value)) {
            return sprintf('object(%s)', get_class($value));
        }
        if (is_string($value)) {
            return $value;
        }

        return preg_replace("/\n\s*/s", '', var_export($value, true));
    }
}
