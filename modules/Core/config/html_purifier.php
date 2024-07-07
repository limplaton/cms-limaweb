<?php
 

return [
    'enabled' => env('HTML_PURIFY', true),
    'encoding' => 'UTF-8',
    'finalize' => true,
    'cachePath' => storage_path('framework/cache/html-purifier'),
    'cacheFileMode' => 0755,
    'flex' => true,

    'settings' => [
        'default' => [
            'HTML.Allowed' => 'a[href|title|target],abbr,acronym,address,b,bdi,blockquote,br,caption,code,col,colgroup,dd,del,details,dfn,div[data-noedit],dl,dt,em,figcaption,figure,h1,h2,h3,h4,h5,h6,hgroup,hr,i,iframe[src|allowfullscreen|frameborder|width|height],img[width|height|alt|src],ins,kbd,li,main,ol,p,pre,section,small,span[data-mention-id|data-notified|data-mention-char|data-mention-value|contenteditable],strong,sub,summary,sup,table[border|cellpadding|cellspacing],tbody,td[align|colspan|rowspan],tfoot,th[align|colspan|rowspan|scope],thead,time,tr,track,tt,u,ul,video[loop|autoplay|poster|controls|preload],source[src|type],*[style|class]',

            'CSS.AllowedProperties' => 'font,font-size,font-weight,font-style,font-family,padding-left,padding-right,padding-top,padding-bottom,padding,margin, margin-left,margin-right,margin-top,margin-bottom,color,text-align,letter-spacing,width,height,min-width,min-height,max-width,max-height,overflow,border,border-width,border-style,border-color,border-top,border-bottom,border-right,border-left,line-height,text-decoration,background,background-color,background-image,background-size,background-repeat,background-position,text-transform,word-spacing,opacity,float,position,top,left,right,border-radius,bottom,border-top-left-radius,border-top-right-radius,border-bottom-right-radius,border-bottom-left-radius,list-style,display,filter',

            'AutoFormat.AutoParagraph' => false,
            'AutoFormat.RemoveEmpty' => false,
            'Attr.EnableID' => true,
            'CSS.Trusted' => true,
            'CSS.Proprietary' => true,
            'HTML.SafeIframe' => true,
            'URI.SafeIframeRegexp' => '%^(http://|https://|//)(www.youtube.com/embed/|player.vimeo.com/video/)%',
            'CSS.AllowTricky' => true,
            'Attr.AllowedFrameTargets' => ['_blank'],

            // Images
            'URI.AllowedSchemes' => [
                'http' => true,
                'https' => true,
                'mailto' => true,
                'ftp' => true,
                'nntp' => true,
                'news' => true,
                'tel' => true,
                // Base64 Images
                'data' => true,
            ],

            // These config option disables the pixel checks and allows
            // specifiy e.q. width="auto" or height="auto" for example on images
            'HTML.MaxImgLength' => null,
            'CSS.MaxImgLength' => null,
        ],

        'custom_definition' => [
            'id' => 'CustomHTML5',
            'rev' => (int) str_replace('.', '', \Modules\Core\App\Application::VERSION),
            'debug' => env('APP_DEBUG', false),
            'elements' => [
                // http://developers.whatwg.org/sections.html
                ['section', 'Block', 'Flow', 'Common'],
                ['nav',     'Block', 'Flow', 'Common'],
                ['article', 'Block', 'Flow', 'Common'],
                ['aside',   'Block', 'Flow', 'Common'],
                ['header',  'Block', 'Flow', 'Common'],
                ['footer',  'Block', 'Flow', 'Common'],

                // Content model actually excludes several tags, not modelled here
                ['address', 'Block', 'Flow', 'Common'],
                ['hgroup', 'Block', 'Required: h1 | h2 | h3 | h4 | h5 | h6', 'Common'],

                // http://developers.whatwg.org/grouping-content.html
                ['figure', 'Block', 'Optional: (figcaption, Flow) | (Flow, figcaption) | Flow', 'Common'],
                ['figcaption', 'Inline', 'Flow', 'Common'],

                // http://developers.whatwg.org/the-video-element.html#the-video-element
                ['video', 'Block', 'Optional: (source, Flow) | (Flow, source) | Flow', 'Common', [
                    'loop' => 'Bool',
                    'autoplay' => 'Bool',
                    'src' => 'URI',
                    'type' => 'Text',
                    'width' => 'Length',
                    'height' => 'Length',
                    'poster' => 'URI',
                    'preload' => 'Enum#auto,metadata,none',
                    'controls' => 'Bool',
                ]],

                ['source', 'Block', 'Flow', 'Common', [
                    'src' => 'URI',
                    'type' => 'Text',
                ]],

                // http://developers.whatwg.org/text-level-semantics.html
                ['s',    'Inline', 'Inline', 'Common'],
                ['var',  'Inline', 'Inline', 'Common'],
                ['sub',  'Inline', 'Inline', 'Common'],
                ['sup',  'Inline', 'Inline', 'Common'],
                ['mark', 'Inline', 'Inline', 'Common'],
                ['wbr',  'Inline', 'Empty', 'Core'],

                // http://developers.whatwg.org/edits.html
                ['ins', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
                ['del', 'Block', 'Flow', 'Common', ['cite' => 'URI', 'datetime' => 'CDATA']],
            ],

            'attributes' => [],
        ],

        'custom_attributes' => [
            ['a', 'target', 'Enum#_blank,_self,_target,_top'],
            ['div', 'align', 'Enum#left,right,center'],

            // Mention
            ['span', 'data-mention-id', 'Number'],
            ['span', 'data-notified', 'Text'],
            ['span', 'data-mention-char', 'Text'],
            ['span', 'data-mention-value', 'Text'],
            ['span', 'contenteditable', 'Enum#true,false'],

            // Content builder
            ['div', 'data-noedit', 'Bool'],
        ],

        'custom_elements' => [
            ['u', 'Inline', 'Inline', 'Common'],
            ['iframe', 'Inline', 'Flow', 'Common', [
                'src' => 'URI#embedded',
                'width' => 'Length',
                'height' => 'Length',
                'name' => 'ID',
                'scrolling' => 'Enum#yes,no,auto',
                'frameborder' => 'Enum#0,1',
                'allow' => 'Text',
                'allowfullscreen' => 'Bool',
                'webkitallowfullscreen' => 'Bool',
                'mozallowfullscreen' => 'Bool',
                'longdesc' => 'URI',
                'marginheight' => 'Pixels',
                'marginwidth' => 'Pixels',
            ],
            ],
        ],
    ],
];
