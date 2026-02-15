@extends( 'layout.dashboard' )

@section( 'layout.dashboard.body.with-header' )
    @include( 'common.dashboard.title' )
    <mynexopos-authentify>
        <div>
            <div class="w-full md:w-3/5 lg:w-2/6 xl:w-2/5 rounded-lg border ns-box border-blue-400 p-2">
                <h2 class="text-2xl text-center">{{ __m( 'Authentify to MyNexoPOS', 'MyNexoPOS' ) }}</h2>
                <p class="text-sm text-center text-fontcolor">{{ __m( 'Link your store with my.nexopos.com', 'MyNexoPOS' ) }}</p>
                <p class="py-2 text-center text-fontcolor-soft">{{ __m( 'Looks like your store is not yet linked to my.nexopos.com. If you would like to be able to update your system with just one click, consider connecting your account.', 'MyNexoPOS' ) }}</p>
                <div class="flex justify-center">
                    <a class="rounded-lg px-3 py-1 bg-secondary text-white font-semibold" href="{{ route( 'mynexopos.authorization' ) }}">{{ __m( 'Authenticate', 'MyNexoPOS' ) }}</a>
                </div>
            </div>
        </div>
    </mynexopos-authentify>
@endsection

@section( 'layout.dashboard.footer' )
    @parent
    <script>
        Vue.component( 'mynexopos-authentify', {
            mounted() {
            }
        });
    </script>
@endsection