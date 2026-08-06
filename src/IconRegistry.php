<?php

namespace LyraDs\Blade;

final class IconRegistry
{
    /**
     * Curated icon names mirrored from the React package registry.
     *
     * @var list<string>
     */
    public const NAMES = [
        'archive',
        'arrow-left',
        'arrow-right',
        'arrow-up-right',
        'bell',
        'book-open',
        'calendar',
        'chart-line',
        'check',
        'chevron-down',
        'chevron-left',
        'chevron-right',
        'chevrons-left',
        'chevrons-right',
        'chevrons-up-down',
        'circle',
        'circle-alert',
        'circle-check',
        'circle-dot',
        'circle-x',
        'cloud-upload',
        'code',
        'copy',
        'credit-card',
        'download',
        'ellipsis',
        'external-link',
        'eye',
        'file',
        'file-archive',
        'file-plus',
        'file-spreadsheet',
        'file-text',
        'film',
        'filter',
        'folder',
        'folder-open',
        'github',
        'globe',
        'hard-drive',
        'heart',
        'house',
        'image',
        'inbox',
        'info',
        'layout-dashboard',
        'layout-grid',
        'link',
        'list',
        'lock',
        'log-out',
        'mail',
        'message-circle',
        'minus',
        'moon',
        'music',
        'package',
        'pencil',
        'plus',
        'rocket',
        'scale',
        'search',
        'send',
        'settings',
        'shield',
        'sliders-horizontal',
        'sparkles',
        'star',
        'sun',
        'terminal',
        'timer',
        'trash-2',
        'triangle-alert',
        'upload',
        'user',
        'user-plus',
        'users',
        'x',
        'zap',
    ];

    public static function contains(?string $name): bool
    {
        return $name !== null && in_array($name, self::NAMES, true);
    }
}
