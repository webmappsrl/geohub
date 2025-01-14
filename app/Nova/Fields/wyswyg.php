<?php

namespace App\Nova\Fields;

use Alfonsobries\NovaTrumbowyg\NovaTrumbowyg;

class NovaWyswyg extends NovaTrumbowyg
{
    public function __construct($name, $attribute = null, $resolveCallback = null)
    {
        parent::__construct($name, $attribute, $resolveCallback);

        // Imposta le opzioni di default
        $this->options([
            'autogrow' => true,
            'imageWidthModalEdit' => true,
            'urlProtocol' => true,
            'btns' => [
                ['viewHTML'],
                ['undo', 'redo'],
                ['formatting'],
                ['strong', 'em', 'del'],
                ['superscript', 'subscript'],
                ['link'],
                ['insertImage'],
                ['justifyLeft', 'justifyCenter', 'justifyRight', 'justifyFull'],
                ['unorderedList', 'orderedList'],
                ['horizontalRule'],
                ['removeformat'],
                ['fullscreen'],
            ],
        ]);
    }
}
