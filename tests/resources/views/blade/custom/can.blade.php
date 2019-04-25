@can('update', $user)
can update user
@endcan
@cannot('update', $user)
can not update user
@endcannot
@can('create', 'Rebet\\Tests\\Mock\\User')
can create user(absolute class name 1)
@endcan
@can('create', '\\Rebet\\Tests\\Mock\\User')
can create user(absolute class name 2)
@endcan
@can('create', '@mock\\User')
can create user(relative class name)
@endcan
@can('create', '@mock\\Address', $addresses)
Can create an address when the addresses count less than 5.
@else
Can not create an address when the user is guest or the addresses count greater equal 5.
@endcan
