<?php

if (! function_exists('assetUrl')) {

    /**
     * Prints the absolute url of an asset.
     *
     * @param  string $fileName
     * @return string
     */
    function assetUrl(string $fileName = '')
    {
        $assets = url('themes/'.\App\Models\Theme::getActiveTheme().'/assets');
        if($fileName) {
            $assets .= "/".$fileName;
        }
        return $assets;
    }
}

if (! function_exists('cssUrl')) {

    /**
     * Prints url to the css file with the providen filename
     * If no file name is provided prints the directory of the css
     *
     * @param  string $fileName
     * @return string
     */
    function cssUrl(string $fileName = '')
    {
        return \App\Models\Theme::cssUrl($fileName);
    }
}


if (! function_exists('imageUrl')) {

    /**
     * Prints url to the image file with the providen filename
     * If no file name is provided prints the directory of the image
     *
     * @param  string $fileName
     * @return string
     */
    function imageUrl(string $fileName = '')
    {
        return \App\Models\Theme::imageUrl($fileName);
    }
}

if (! function_exists('jsUrl')) {

    /**
     * Prints url to the js file with the providen filename
     * If no file name is provided prints the directory of the js
     *
     * @param  string $fileName
     * @return string
     */
    function jsUrl(string $fileName = '')
    {
        return \App\Models\Theme::jsUrl($fileName);
    }
}

if (! function_exists('fontUrl')) {

    /**
     * Prints url to the font file with the providen filename
     * If no file name is provided prints the directory of the font
     *
     * @param  string $fileName
     * @return string
     */
    function fontUrl(string $fileName = '')
    {
        return \App\Models\Theme::fontUrl($fileName);
    }
}

if (! function_exists('shareUrl')) {
    /**
     * Get all links to share content in social media
     *
     * @param  string $url   to be shared
     * @param  string $title (optional) if you want to append text to share
     * @return array all social links
     */
    function shareUrl(string $url, string $title = '')
    {
        $links = [];
        $links['facebook'] = "https://www.facebook.com/sharer.php?u=" . $url;
        $links['twitter'] = "https://twitter.com/intent/tweet?url={$url}&text={$title}";
        $links['google'] = "https://plus.google.com/share?url={$url}&text={$title}";
        $links['linkedin'] = "https://www.linkedin.com/shareArticle?mini=true&url={$url}&title={$title}";
        $links['viber'] = "viber://forward?text={$url}";
        $links['whatsapp'] = "whatsapp://send?text={$url}";
        return $links;
    }
}
function uploadsURL($filePath)
{
    return Storage::disk('public')->url($filePath);
}
