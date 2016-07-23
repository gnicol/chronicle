<?php
/**
 * Display image tag for associated image. Image src is taken to
 * be the image URI for the element's associated content record.
 * The element must be content type enhanced to get the associated
 * content.
 *
 * Several options are supported to influence the presentation:
 *
 *      width - fix the image width (in pixels)
 *     height - fix the image height
 *   maxWidth - limit the image width
 *  maxHeight - limit the image height
 *       link - link to the original image
 *     target - target for the image link
 *
 * @copyright   2011 Perforce Software. All rights reserved.
 * @license     Please see LICENSE.txt in top-level folder of this distribution.
 * @version     <release>/<patch>
 */
class Content_Form_Decorator_DisplayImage extends Content_Form_Decorator_DisplayFileLink
{
    /**
     * Produce html tag for current element and given label.
     * Extends parent to produce an image tag.
     *
     * @param   string  $label      the label to include in the tag.
     * @param   array   $params     the paramaters to provide to the Uri function
     * @return  string  the rendered html tag.
     */
    protected function _renderHtmlTag($label, $params)
    {
        // some browsers have more aggressive cache, include the
        // version in the uri to avoid stale cache
        $record = $this->getElement()->getContentRecord();
        $params = (array) $params;
        if (!isset($params['version'])) {
            $params['v'] = $record->toP4File()->getStatus('headRev');
        }

        // image dimensions are routinely stored in content field metadata
        // if present, set them on image to optimize image loading/rendering.
        $info        = $record->getFieldMetadata($this->getElement()->getName());
        $size        = isset($info['dimensions']) ? $info['dimensions'] : array();
        $width       = isset($size['width'])      ? $size['width']      : null;
        $height      = isset($size['height'])     ? $size['height']     : null;

        // decorator options can influence the size of the displayed image.
        $fixedWidth  = $this->getOption('width');
        $fixedHeight = $this->getOption('height');
        $maxWidth    = $this->getOption('maxWidth');
        $maxHeight   = $this->getOption('maxHeight');

        // if we know the actual dimensions, we can compute the final size based on options.
        // otherwise, if fixed dimensions have been given, we'll use them as given.
        if ($width && $height) {
            list($width, $height) = $this->_computeSize(
                $width, $height, $fixedWidth, $fixedHeight, $maxWidth, $maxHeight
            );
        } else {
            $width  = $fixedWidth;
            $height = $fixedHeight;
        }

        // allow size options to influence request params (for server-side scaling).
        $options = array('width', 'height', 'maxWidth', 'maxHeight');
        foreach ($options as $option) {
            if ($this->getOption($option)) {
                $params[$option] = $this->getOption($option);
            }
        }

        // prepare an extra class depending on the image dimensions
        $orientation = '';
        if ($width > $height) {
            $orientation = 'landscape';
        } else if ($width < $height) {
            $orientation = 'portrait';
        } else if ($width && $height) {
            $orientation = 'square';
        }

        // build the image tag - if no size set, don't output width/height
        // if 'asBackground' set, render as a div with a background image.
        $noSize = $this->getOption('noSize');
        if ($this->getOption('asBackground')) {
            $html = '<div'
                . ' class="image"'
                . ' orientation="' . $orientation . '"'
                . ' title="' . htmlentities($label) . '"'
                . ' style="background-image: url(\'' . htmlentities($record->getUri('image', $params)) . '\'); '
                . ($width  && !$noSize ? ' width: '  . htmlentities($width)  . 'px; ' : '')
                . ($height && !$noSize ? ' height: ' . htmlentities($height) . 'px; ' : '')
                . '"></div>';
        } else {
            $html = '<img'
                . ' orientation="' . $orientation . '"'
                . ' alt="' . htmlentities($label) . '"'
                . ' src="' . htmlentities($record->getUri('image', $params)) . '"'
                . ($width  && !$noSize ? ' width="'  . htmlentities($width)  . '"' : '')
                . ($height && !$noSize ? ' height="' . htmlentities($height) . '"' : '')
                . '>';
        }

        // options can specify that the image is a link.
        // if link is not false, we will wrap the image in a link tag.
        // if link is a string (other than '1'), assume it is the href
        // to use; otherwise, link to the original image.
        // exclude sizing options when building image link.
        $link = $this->getOption('link');
        if ($link) {
            $target = $this->getOption('target');
            if ($target == '_lightbox') {
                $target = "";
                $click  = "new p4cms.ui.LightBox({href:this.href, opener: this}).startup(); return false;";
            }
            $href   = is_string($link) && $link !== "1"
                ? $this->getOption('link')
                : $record->getUri('image', array_diff_key($params, array_flip($options)));
            $link   = '<a '
                    . '    href="' . htmlentities($href) . '"'
                    . '  target="' . $target . '"'
                    . ' onclick="' . (isset($click) ? $click : '') . '">';

            // ensure the link we crafted points at the active branch
            $filter = new P4Cms_Filter_BranchifyUrls;
            $link   = $filter->filter($link);

            $html   = $link
                    . $html
                    . '</a>';
        }

        // output caption if one was specified.
        if ($this->getOption('caption')) {
            $html .= '<div class="image-caption">' . $this->getOption('caption') . '</div>';
        }

        return $html;
    }

    /**
     * Compute the final size of the image according to the original dimensions
     * and the given fixed or limited width/height options.
     *
     * @param   int     $actualWidth    the actual width of the original image
     * @param   int     $actualHeight   the actual width of the original image
     * @param   int     $fixedWidth     optional - desired fixed width
     * @param   int     $fixedHeight    optional - desired fixed height
     * @param   int     $maxWidth       optional - width to limit image to
     * @param   int     $maxHeight      optional - height to limit image to
     * @return  array   final width and height (width first, height second)
     */
    protected function _computeSize($actualWidth, $actualHeight, $fixedWidth, $fixedHeight, $maxWidth, $maxHeight)
    {
        $ratio = $actualWidth / $actualHeight;

        //  - start with original size if no fixed dimensions specified
        //  - if only one dimension was specified, compute the other one
        //    to keep the aspect ration of the original image
        //  - if both given, use them as given
        if (!$fixedWidth && !$fixedHeight) {
            $width  = $actualWidth;
            $height = $actualHeight;
        } else if (!$fixedWidth) {
            $width  = round($fixedHeight * $ratio);
            $height = $fixedHeight;
        } else if (!$fixedHeight) {
            $width  = $fixedWidth;
            $height = round($fixedWidth / $ratio);
        } else {
            $width  = $fixedWidth;
            $height = $fixedHeight;
        }

        // lower image dimensions if they exceed maximum dimensions
        if ($maxHeight && $maxHeight < $height) {
            $width  = round($width * $maxHeight / $height);
            $height = $maxHeight;
        }
        if ($maxWidth && $maxWidth < $width) {
            $height = round($height * $maxWidth / $width);
            $width  = $maxWidth;
        }

        return array($width, $height);
    }
}
