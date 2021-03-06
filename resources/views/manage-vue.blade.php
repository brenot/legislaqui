<!DOCTYPE html>
<html>
<head>
	<title>Laravel Vue JS Item CRUD</title>
	<meta id="token" name="token" value="{{ csrf_token() }}">
	<link rel="stylesheet" type="text/css" href="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha/css/bootstrap.css">
</head>
<body>

	<div class="container" id="manage-vue">

		<div class="row">
		    <div class="col-lg-12 margin-tb">
		        <div class="pull-left">
		            <h2>Laravel Vue JS Item CRUD</h2>
		        </div>
		        <div class="pull-right">
				<button type="button" class="btn btn-success" data-toggle="modal" data-target="#create-item">Create User</button>
		        </div>
		    </div>
		</div>

		<!-- Item Listing -->
		<table class="table table-bordered">
			<tr>
				<th>Nome</th>
				<th>Email</th>
				<th width="200px">Action</th>
			</tr>
			<tr v-for="item in items">
				<td>@{{ item.name }}</td>
                <td>@{{ item.email }}</td>
                <td>
                    <button class="btn btn-primary" @click.prevent="editItem(item)">Edit</button>
                    <button class="btn btn-danger" @click.prevent="deleteItem(item)">Delete</button>
                </td>
            </tr>
        </table>

        <!-- Pagination -->
        <nav>
            <ul class="pagination">
                <li v-if="pagination.current_page > 1">
                    <a href="#" aria-label="Previous"
                       @click.prevent="changePage(pagination.current_page - 1)">
                        <span aria-hidden="true">«</span>
                    </a>
                </li>
                <li v-for="page in pagesNumber"
                    v-bind:class="[ page == isActived ? 'active' : '']">
                    <a href="#"
                       @click.prevent="changePage(page)">@{{ page }}</a>
                </li>
                <li v-if="pagination.current_page < pagination.last_page">
                    <a href="#" aria-label="Next"
                       @click.prevent="changePage(pagination.current_page + 1)">
                        <span aria-hidden="true">»</span>
                    </a>
                </li>
            </ul>
        </nav>

        <!-- Create Item Modal -->
        <div class="modal fade" id="create-item" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title" id="myModalLabel">Create User</h4>
                    </div>
                    <div class="modal-body">

                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="createUser">

                            {{ csrf_field() }}

                            <div class="form-group">
                                <label for="title">Nome:</label>
                                <input type="text" name="name" class="form-control" v-model="newItem.name" />
                                <span v-if="formErrors['name']" class="error text-danger">@{{ formErrors['name'] }}</span>
                            </div>

                            <div class="form-group">
                                <label for="title">Email:</label>
                                <input type="text" name="email" class="form-control" v-model="newItem.email" />
                                <span v-if="formErrors['email']" class="error text-danger">@{{ formErrors['email'] }}</span>
                            </div>

                            <div class="form-group">
                                {{ Form::label('UF', 'UF', array('class' => 'control-label')) }}
                                {!! Form::select('uf', $states, 'RJ', ['class' => 'form-control']) !!}
                            </div>

                            <div class="form-group">
                                {{ Form::label('Função', 'Função', array('class' => 'control-label')) }}
                                {!! Form::select('role_id', $roles, null, ['class' => 'form-control']) !!}
                            </div>

                            <div class="form-group">
                                {{ Form::label('CPF', 'CPF', array('class' => 'control-label')) }}
                                {{ Form::text('cpf', null,
                                     array('required',
                                           'class'=>'form-control',
                                           'id' => 'cpf',
                                           )) }}
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Submit</button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>

        <!-- Edit Item Modal -->
        <div class="modal fade" id="edit-item" tabindex="-1" role="dialog" aria-labelledby="myModalLabel">
            <div class="modal-dialog" role="document">
                <div class="modal-content">
                    <div class="modal-header">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">×</span></button>
                        <h4 class="modal-title" id="myModalLabel">Edit User</h4>
                    </div>
                    <div class="modal-body">

                        <form method="POST" enctype="multipart/form-data" v-on:submit.prevent="updateItem(fillItem.id)">

                            <div class="form-group">
                                <label for="title">Nome:</label>
                                <input type="text" name="name" class="form-control" v-model="fillItem.name" />
                                <span v-if="formErrorsUpdate['name']" class="error text-danger">@{{ formErrorsUpdate['name'] }}</span>
                            </div>

                            <div class="form-group">
                                <label for="title">Email:</label>
                                <input type="text" name="email" class="form-control" v-model="fillItem.email" />
                                <span v-if="formErrorsUpdate['email']" class="error text-danger">@{{ formErrorsUpdate['email'] }}</span>
                            </div>

                            <div class="form-group">
                                <button type="submit" class="btn btn-success">Submit</button>
                            </div>

                        </form>

                    </div>
                </div>
            </div>
        </div>

    </div>

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.1.0/jquery.min.js"></script>
<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/twitter-bootstrap/4.0.0-alpha/js/bootstrap.min.js"></script>

<script type="text/javascript" src="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/js/toastr.min.js"></script>
<link href="//cdnjs.cloudflare.com/ajax/libs/toastr.js/latest/css/toastr.min.css" rel="stylesheet">

<script type="text/javascript" src="https://cdnjs.cloudflare.com/ajax/libs/vue/1.0.26/vue.min.js"></script>
<script type="text/javascript" src="https://cdn.jsdelivr.net/vue.resource/0.9.3/vue-resource.min.js"></script>

<script type="text/javascript" src="/js/item.js"></script>

</body>
</html>
