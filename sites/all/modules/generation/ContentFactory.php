<?php
/**
 * Class to contain code used by the generation module to create content entries.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Generation_ContentFactory
{
    /**
     * Creates a content entry based on the provided content type; uses a randomly-selected content type.
     *
     * @return P4Cms_Content
     */
    public function createEntry()
    {
        $entry = new P4Cms_Content();

        $types = P4Cms_Content_Type::fetchAll();
        $position = rand(0, $types->count()-1);
        $types->seek($position);
        $type = $types->current();

        $entry->setContentType($type);

        $generationUtility = new Generation_Utility;

        // pseudo-random content
        foreach ($type->getFormElements() as $element) {
            // if required, add it
            if ($element->isRequired()) {
                $name = strtolower($element->getName());
                if ($name == 'file') {
                    $fileContent = "Generated file content:\n";
                    $target      = rand(5, 15);

                    for ($x = 0; $x < $target; $x++) {
                        $fileContent .= $generationUtility->getParagraph();
                    }
                    $entry->setValue('file',  $fileContent)
                          ->setFieldMetadata(
                            'file',
                            array('filename' => 'file.txt', 'mimeType' => 'text/plain')
                          );
                } else if ($name == 'image') {
                    $entry->setValue('image',  'Generated image content.')
                          ->setFieldMetadata(
                            'file',
                            array('filename' => 'image.jpg', 'mimeType' => 'image/jpg')
                          );
                } else if ($name == 'title') {
                    // use the a random sentence as the title
                    $title = $generationUtility->getSentence();
                    $entry->setValue($element->getName(), $title);
                } else if ($name == 'date') {
                    $date = new Zend_Date();
                    $entry->setValue($element->getName(), $date->toString('MMMM d, yyyy'));
                } else {
                    $entry->setValue($element->getName(), $generationUtility->getParagraph());
                }
            } else if (strtolower($element->getName()) == 'body') {
                $content = '';
                $target  = rand(2, 10);

                for ($x = 0; $x < $target; $x++) {
                    $content .= $generationUtility->getParagraph();
                }
                $entry->setValue($element->getName(), $content);
            }
        }

        // workflow states, if applicable
        // chose publish state at least 50% of the time, don't transition for some percentage < 25%
        $workflowsByType  = Workflow_Model_Workflow::fetchTypeMap();
        if (array_key_exists($type->getId(), $workflowsByType) && rand(0, 3) > 0) {
            $defaultState     = $workflowsByType[$type->getId()]->getDefaultState();

            if ((rand(0, 1) === 0) && $defaultState->hasTransition('published')) {
                $targetTransition = $defaultState->getTransitionModel('published');
            } else {
                $transitions      = $defaultState->getValidTransitionsFor($entry);
                $target           = rand(0, count($transitions)-1);
                $targetTransition = $transitions[$target];
            }

            // if there are any problems, use the default state
            try {
                $targetState = $targetTransition->getToState();
            }
            catch (Workflow_Exception $e) {
                $targetState = $defaultState;
            }

            $workflowsByType[$type->getId()]->setStateOf($entry, $targetState);
        }

        $entry->setValue('url', array('auto' => true, 'path' => $entry->getTitle()));

        return $entry;
    }
}
