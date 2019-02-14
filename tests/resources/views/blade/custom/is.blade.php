@is('user')
user
@endis
@is('admin')
admin
@endis
@is('user:editable')
user and editable
@endis
@is('admin','user')
admin or user
@endis
@is('guest')
Guest
@else
Not Guest.
@endis