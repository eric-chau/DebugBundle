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

use Symfony\Component\Console\Helper\DescriptorHelper;
use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

use BackBee\Console\AbstractCommand;

/**
 * A console command for retrieving information about event dispatcher.
 *
 * A (proud) adaptation of Symfony framework's one
 *
 * @author  Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class DebugEventDispatcherCommand extends AbstractCommand
{
    /**
     * {@inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:event-dispatcher')
            ->setDefinition([
                new InputArgument('event', InputArgument::OPTIONAL, 'An event name')
            ])
            ->setDescription('Displays configured listeners for an application')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command displays all configured listeners:
  <info>php %command.full_name%</info>
To get specific listeners for an event, specify its name:
  <info>php %command.full_name% kernel.request</info>
EOF
            )
        ;
    }

    /**
     * {@inheritdoc}
     *
     * @throws \LogicException
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $dispatcher = $this->getApplication()->getApplication()->getEventDispatcher();

        if ($event = $input->getArgument('event')) {
            if (!$dispatcher->hasListeners($event)) {
                $formatter = $this->getHelperSet()->get('formatter');
                $formattedBlock = $formatter->formatBlock(
                    sprintf('[NOTE] The event "%s" does not have any registered listeners.', $event),
                    'fg=yellow',
                    true
                );
                $output->writeln($formattedBlock);
                return;
            }
            $options = ['event' => $event];
        } else {
            $options = [];
        }

        $this->describeEventDispatcherListeners($output,$dispatcher, $options);
    }

    /**
     * Returns an human readable description of a collection of event listeners
     * in console
     *
     * @param OutputInterface $output
     * @param EventDispatcherInterface $eventDispatcher
     * @param array $options the Console input options
     */
    protected function describeEventDispatcherListeners(OutputInterface $output, EventDispatcherInterface $eventDispatcher, array $options)
    {
        $event = array_key_exists('event', $options) ? $options['event'] : null;
        $label = 'Registered listeners';
        if (null !== $event) {
            $label .= sprintf(' for event <info>%s</info>', $event);
        } else {
            $label .= ' by event';
        }

        $registeredListeners = $eventDispatcher->getListeners($event, true);

        if (null !== $event) {
            $output->writeln($label);
            $output->writeln(sprintf("\n<info>[Event]</info> %s\n", $event));
            $this->renderTable($output, $registeredListeners);
        } else {
            ksort($registeredListeners);

            foreach ($registeredListeners as $eventListened => $eventListeners) {
                $output->writeln(sprintf("\n<info>[Event]</info> %s\n", $eventListened));
                krsort($eventListeners);
                $this->renderTable($output, $eventListeners);
            }
        }
    }

    /**
     * Render a Console Table view from listeners collection
     *
     * @param OutputInterface $output
     * @param array<EventListener> $eventListeners a collection of event listeners
     *
     * @return void intented to render a Table
     */
    private function renderTable(OutputInterface $output, array $eventListeners)
    {
        $table = new Table($output);
        $table->getStyle()->setCellHeaderFormat('%s');
        $table->setHeaders(array('Order', 'Callable'));
        $order = 1;

        foreach ($eventListeners as $eventListened) {
            $table->addRow(array(sprintf('# %d', $order++), $this->formatCallable($eventListened)));
        }

        $table->render();
    }

    /**
     * @param callable $callable
     *
     * @return string
     */
    private function formatCallable($callable)
    {
        if (is_array($callable)) {
            if (is_object($callable[0])) {
                return sprintf('%s::%s()', get_class($callable[0]), $callable[1]);
            }
            return sprintf('%s::%s()', $callable[0], $callable[1]);
        }
        if (is_string($callable)) {
            return sprintf('%s()', $callable);
        }
        if ($callable instanceof \Closure) {
            return '\Closure()';
        }
        if (method_exists($callable, '__invoke')) {
            return sprintf('%s::__invoke()', get_class($callable));
        }
        throw new \InvalidArgumentException('Callable is not describable.');
    }
}
