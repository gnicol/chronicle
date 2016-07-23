<?php
/**
 * Class to contain code used by the generation module to create pseudo-realisitc text.
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Generation_Utility
{
    /**
     * Generates a paragraph of random text.
     *
     * @return string A generated paragraph, with tab and newline.
     */
    public function getParagraph()
    {
        $paragraph = '';
        $target    = rand(3, 10);

        for ($x = 0; $x < $target; $x++) {
            $paragraph .= $this->getSentence() . '  ';
        }
        return "\t" . trim($paragraph) . "\n";
    }

    /**
     * Generates a sentence of randomly created words.
     *
     * @return string A generated sentence with puncuation and trailing spaces.
     */
    public function getSentence()
    {
        $punctuation = '!?.';
        $sentence    = '';
        $target      = rand(3, 10);

        for ($x = 0; $x < $target; $x++) {
            $sentence .= $this->getWord() . ' ';
        }

        $index = rand(0, strlen($punctuation)-1);
        return ucfirst(trim($sentence)) . $punctuation[$index];
    }

    /**
     * Generates random, plausible appearing words.
     *
     * @return string A randomly constructed word.
     */
    public function getWord()
    {
        $lastChar = '';
        $word     = '';
        $target   = rand(3, 10);

        for ($x = 0; $x < $target; $x++) {
            $lastChar = $this->nextChar($lastChar);
            $word .= $lastChar;
        }
        return $word;
    }

    /**
     * Returns a randomly generated character.
     * Contains logic which looks at the previous character to ensure a plausable mix between consonants and vowels.
     *
     * @param string $previousChar The previously generated character.
     */
    public function nextChar($previousChar = null)
    {
        $vowels     = 'aeiouy';
        $consonants = 'bcdfghjklmnpqrstvwxz';
        $letters    = 'abcdefghijklmnopqrstuvwxyz';

        // if no previous character, pick one randomly
        if (!$previousChar) {
            $index = rand(0, strlen($letters)-1);
            return $letters[$index];
        }

        // return a vowel if previous char was a consonant
        if (strpos($consonants, $previousChar) !== false) {
            $index = rand(0, strlen($vowels)-1);
            return $vowels[$index];
        }

        // weight consonants higher
        if (rand(0, 10) < 6) {
            $index = rand(0, strlen($consonants)-1);
            return $consonants[$index];
        } else {
            $index = rand(0, strlen($letters)-1);
            return $letters[$index];
        }
    }
}