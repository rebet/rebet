<?php
declare(strict_types=1);

return [
    '@errors' => [
        'outer' => '<article class="message is-small is-danger has-text-left" style="margin-top: 8px;"><ul class="message-body" style="padding: 5px;">:messages</ul></article>',
        'inner' => '<li><span class="icon"><i class="fas fa-exclamation-triangle"></i></span> :message</li>',
        'class' => 'is-danger',
        'icon'  => '<span class="icon is-small is-right"><i class="fas fa-exclamation-triangler"></i></span>',
    ],

    'http' => [
        403 => [
            'title'  => null,
            'detail' => '指定のページを表示できません。',
        ],
        404 => [
            'title'  => null,
            'detail' => '指定のURLは存在しないか、既に有効期限が切れています。',
        ],
        500 => [
            'title'  => null,
            'detail' => "予期せぬエラーが発生致しました。\n大変お手数をお掛け致しますが時間をおいてから再度お試しください。",
        ],
    ],

    'signin_failed' => 'サインインに失敗しました。メールアドレス／パスワードをご確認の上、再度お試しください。',
];
