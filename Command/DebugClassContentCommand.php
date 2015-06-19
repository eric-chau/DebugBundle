<?php

/*
 * Copyright (c) 2011-2015 Lp digital system
 *
 * This file is part of BackBee Standard Edition.
 *
 * BackBee is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * BackBee Standard Edition is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with BackBee Standard Edition. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBee\Bundle\DebugBundle\Command;

use Symfony\Component\Console\Helper\Table;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Output\OutputInterface;
use BackBee\ClassContent\ClassContentManager;
use BackBee\Console\AbstractCommand;

/**
 * List all available class contents.
 *
 * @author Mickaël Andrieu <mickael.andrieu@lp-digital.fr>
 */
class DebugClassContentCommand extends AbstractCommand
{
    /**
     * @var \BackBee\ClassContent\ClassContentManager The BackBee ClassContent manager
     */
    private $classContentManager;

    /**
     * @var array All available contents in application
     */
    private $classContents;

    /**
     * @{inheritdoc}
     */
    protected function configure()
    {
        $this
            ->setName('debug:class-content')
            ->setDefinition([
                new InputArgument('classname', InputArgument::OPTIONAL, 'A ClassContent classname (ex: "BackBee\ClassContent\Element\Date")'),
            ])
            ->setDescription('List all availables class contents')
            ->setHelp(<<<EOF
The <info>%command.name%</info> command can help you when you are contributing new class contents.
EOF
            )
        ;
    }

    /**
     * @{inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->classContentManager = $this->getApplication()
            ->getApplication()
            ->getContainer()
            ->get('classcontent.manager')
        ;

        $this->classContents = $this->classContentManager->getAllClassContentClassnames();

        /*
         * @todo: better to use isEnabled to hide this function if no class contents were found ?
         */
        if (!is_array($this->classContents) || count($this->classContents) <= 0) {
            throw new \LogicException('You don`t have any Class content in application');
        }

        if ($input->getArgument('classname')) {
            $output->writeln($this->describeClassContent($input->getArgument('classname')));

            return;
        }

        $output->writeln($this->getHelper('formatter')->formatSection('Class Contents', 'List of available Class Contents'));
        $output->writeln('');

        $headers = array('Name', 'Classname');
        $table = new Table($output);
        $table->setHeaders($headers)
            ->setStyle('compact')
        ;

        foreach ($this->classContents as $classContent) {
            $instance = new $classContent();
            $table->addRow([$instance->getProperty('name'), $classContent]);
        }

        $table->render();
    }

    /**
     * Returns a complete text description of a Class Content.
     *
     * @param mixen $classContent An instance of Class Content is expected
     *
     * @return string|InvalidArgumentException InvalidArgumentException occurs if the class content is not found.
     */
    private function describeClassContent($classContent)
    {
        if (!in_array($classContent, $this->classContents)) {
            throw new \InvalidArgumentException(sprintf('The ClassContent %s does\'nt exists', $classContent));
        }

        $instance = new $classContent();

        $returnAccepts = function ($instance) {
            $concat = "\n";
            $elements = [];
            foreach ($instance->getAccept() as $accept) {
                if (!in_array($accept, $elements)) {
                    $concat .= '            '.$accept[0] ."\n";
                    $elements[] = $accept;
                }

            }

            return $concat;
        };

        $description = [
            '<comment>Label</comment>         '.$instance->getLabel(),
            '<comment>Accept</comment>      '.$returnAccepts($instance),
            '<comment>ImageName</comment>   '.$instance->getImageName(),
            '<comment>Properties</comment>',
        ];

        foreach ($instance->getProperty() as $propertyName => $propertyValue) {
            $description[] = "<comment>$propertyName</comment>        ".$this->formatParameter($propertyValue);
        }

        return implode("\n", $description)."\n";
    }

    /**
     * Returns an human readable string for a value.
     *
     * @param mixin $value The value
     *
     * @return string
     */
    protected function formatParameter($value)
    {
        if (is_bool($value) || is_array($value) || (null === $value)) {
            return json_encode($value);
        }

        return $value;
    }
}
