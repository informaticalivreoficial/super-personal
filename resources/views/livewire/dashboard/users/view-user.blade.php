<div>
    <div class="content-header">
        <div class="container-fluid">
            <div class="row mb-2">
                <div class="col-sm-6">
                    <h1><i class="fas fa-user mr-2"></i> Perfil</h1>
                </div>
                <div class="col-sm-6">
                    <ol class="breadcrumb float-sm-right">
                        <li class="breadcrumb-item"><a href="{{route('admin')}}">Painel de Controle</a></li>
                        <li class="breadcrumb-item"><a href="{{route('users.index')}}">Usuários</a></li>
                        <li class="breadcrumb-item active">Perfil</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
    
        <div class="row">
            <div class="col-md-3">
        
              <!-- Profile Image -->
              <div class="card card-teal card-outline">
                <div class="card-body box-profile">
                  <div class="text-center">
                    @php
                        if(!empty($user->avatar) && \Illuminate\Support\Facades\Storage::exists($user->avatar)){
                            $cover = \Illuminate\Support\Facades\Storage::url($user->avatar);
                        } else {
                            if($user->gender == 'masculino'){
                                $cover = url(asset('theme/images/avatar5.png'));
                            }else{
                                $cover = url(asset('theme/images/avatar3.png'));
                            }
                        }
                    @endphp
                    <img class="profile-user-img img-fluid img-circle" src="{{$cover}}" alt="{{$user->name}}">
                  </div>
        
                  <h3 class="profile-username text-center">{{$user->name}}</h3>
        
                  <p class="text-muted text-center">{{--$user->getFuncao()--}}</p>
        
                  <ul class="list-group list-group-unbordered mb-3">
                    <li class="list-group-item">
                      <b>Celular:</b> <a class="float-right">{{$user->cell_phone}}</a>
                    </li>
                    <li class="list-group-item">
                      <b>WhatsApp:</b> <a class="float-right">{{$user->whatsapp}}</a>
                    </li>
                  </ul>
                </div>
                <!-- /.card-body -->
              </div>
              <!-- /.card -->
              
            </div>
            <!-- /.col -->
            <div class="col-md-9">
              <div class="card">          
                <div class="card-body">
                    <h5 class="text-bold">Informações Pessoais</h5>
                    <div class="row mt-3 text-muted">
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>CPF:</b> {{$user->cpf}}</p>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>RG:</b> {{$user->rg}}</p>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>RG/Expedição:</b> {{$user->rg_expedition}}</p>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>Data de Nascimento:</b> {{$user->birthday}}</p>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>Naturalidade:</b> {{$user->naturalness}}</p>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>Estado Civil:</b> {{$user->civil_status}}</p>
                        </div>
                    </div>
                    <hr>
                    <h5 class="text-bold">Informações de Contato</h5>
                    <div class="row mt-3 text-muted">                        
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>Celular:</b> {{$user->cell_phone}}</p>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>WhatsApp:</b> {{$user->whatsapp}}</p>
                        </div>
                        
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>E-mail:</b> {{$user->email}}</p>
                        </div>
                        <div class="col-12 col-sm-6 col-md-6 col-lg-6 col-xl-4">
                            <p><b>E-mail Adicional:</b> {{$user->additional_email}}</p>
                        </div>                  
                    </div>              
                    <hr>
                    <h5 class="text-bold">Endereço</h5>
                    <div class="row mt-3 text-muted">
                        <div class="col-md-6 col-xl-4">
                            <p><b>Endereço:</b> {{$user->street}}</p>
                        </div>
                        <div class="col-md-3 col-xl-4">
                            <p><b>Bairro:</b> {{$user->neighborhood}}</p>
                        </div>
                        <div class="col-md-3 col-xl-2">
                            <p><b>Número:</b> {{$user->number}}</p>
                        </div>
                        
                        <div class="col-md-3 col-xl-2">
                            <p><b>Cep:</b> {{$user->postcode}}</p>
                        </div>
                        <div class="col-md-3 col-xl-4">
                            <p><b>Complemento:</b> {{$user->complement}}</p>
                        </div>
                        <div class="col-md-3 col-xl-4">
                            <p><b>Cidade:</b> {{$user->city}}</p>
                        </div>
                        <div class="col-md-3 col-xl-3">
                            <p><b>Uf:</b> {{$user->state}}</p>
                        </div>
                    </div>
                </div>
              </div>
              <!-- /.nav-tabs-custom -->
            </div>
            <!-- /.col -->
          </div>
   

</div>
