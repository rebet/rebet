@can('update', $user)
can update user
@endcan
@can('create', 'Rebet\\Tests\\Common\\Mock\\User')
can create user(absolute class name)
@endcan
@can('create', '@mock\\User')
can create user(relative class name)
@endcan
