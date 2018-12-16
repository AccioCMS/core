<?php

if (! function_exists('pluginsPath')) {
    /**
     * Error 404
     *
     * @return \Illuminate\Http\Response
     */
    function pluginsPath($path = '')
    {
        $path = str_replace('\\', '/', $path);
        return base_path().'/plugins'.($path ? '/'.$path : "");
    }
}


if (! function_exists('tmpPath')) {
    /**
     * Get temporary path.
     *
     * @param  string $path
     * @return \Illuminate\Http\Response
     */
    function tmpPath($path = '')
    {
        return storage_path('tmp'.($path ? '/'.$path : ""));
    }
}

if (! function_exists('accioPath')) {
    /**
     * Manafarra CMS path.
     *
     * @param  string $path
     * @return string
     */
    function accioPath($path = '')
    {
        return base_path('vendor/acciocms/core/src'.($path ? '/'.$path : ""));
    }
}


if(!function_exists('stubPath')) {
    /**
     * Get stub Path-
     *
     * @param  string $path
     * @param  bool   $stubExtension True if .stub extension should be added in the given path
     * @return string
     */
    function stubPath($path, $stubExtension = true)
    {
        return base_path() . '/vendor/acciocms/core/src/support/stubs/' . $path .($stubExtension ? '.stub' : '');
    }
}
function uploadsPath($extraPath = null)
{
    return config('filesystems.disks.public.path').($extraPath ? "/".$extraPath : '');
}

if(!function_exists('projectDirectory')) {
    /**
     * Get directory of the project
     *
     * @return string
     */
    function projectDirectory()
    {
        $splitRoot = explode(request()->getHost(), Request::root());
        if(isset($splitRoot[1])) {
            return $splitRoot[1];
        }

        return "/";
    }
}
