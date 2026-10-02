@role('user')
user
@endrole
@role('admin')
admin
@endrole
@role('user:editable')
user and editable
@endrole
@role('admin','user')
admin or user
@endrole
@role('guest')
Guest
@else
Not Guest.
@endrole