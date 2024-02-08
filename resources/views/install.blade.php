@extends('app24-core::themes.bitrix24')

@section('content')
    <h1>Install portal #{{ $portal_id ?? 0 }}</h1>
    <script>
        BX24.init(function(){
            console.log('Install...');
            BX24.installFinish();
        });
    </script>
@endsection
