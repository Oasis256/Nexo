@extends( 'layout.base' )
@section( 'layout.base.body' )
<div id="page-container" class="h-full w-full flex items-center overflow-y-auto pb-10 bg-gray-300">
    <div class="container mx-auto p-4 md:p-0 flex-auto items-center justify-center flex">
        <div id="sign-in-box" class="w-full md:w-3/5 lg:w-2/5 xl:w-96">
            <div class="flex justify-center items-center py-6">
                @if ( ! ns()->option->get( 'ns_store_square_logo', false ) )
                <img class="w-32" src="{{ asset( 'svg/nexopos-variant-1.svg' ) }}" alt="NexoPOS">
                @else
                <img src="{{ ns()->option->get( 'ns_store_square_logo' ) }}" alt="NexoPOS">
                @endif
            </div>
            <div class="shadow bg-white p-2 rounded">
            @if ( ! empty( $stores ) )
                <h2 class="font-bold text-center text-3xl text-gray-800">{{ __m( 'Store Selection', 'NsMultiStore' ) }}</h2>
                <p class="text-gray-700 text-center my-4">{{ __m( 'Here is the list of the store you\'re allowed to access.', 'NsMultiStore' ) }}<br>{{ __m( 'Click on one store to access the dashboard.', 'NsMultiStore' ) }}</p>
                <div class="grid grid-cols-1 md:grid-cols-2 grid-rows-1 md:grid-rows-2">
                    @foreach( $stores as $store )
                    <div>
                        @if ( ns()->store->subDomainsEnabled() )
                        <a href="{{ ns()->store->baseProtocol() . $store->slug . '.' . ns()->store->baseDomainName() . '/dashboard' }}" v-for="store of stores" class="border bg-blue-800 cursor-pointer border-gray-200 h-40 relative flex">
                            <div class="h-full w-full object-contain overflow-hidden flex items-center justify-center">
                                @if ( $store->thumb )
                                <img src="{{ $store->thumb }}" alt="{{ $store->name }}">
                                @else
                                <img class="w-24" src="{{ url( '/modules/nsmultistore/assets/images/shop.png' ) }}" alt="{{ $store->name }}">
                                @endif
                            </div>
                            <div class="h-16 bottom-0 absolute w-full z-10 p-2 flex items-center flex-col justify-center text-white font-semibold" style="background: rgb(0,0,0);
    background: linear-gradient(0deg, rgba(0,0,0,0.8379726890756303) 0%, rgba(0,0,0,0.7147233893557423) 32%, rgba(0,212,255,0) 100%);">
                                <span>{{ $store->name }}</span>
                            </div>
                        </a>
                        @else
                        <a href="{{ url( '/dashboard/store/' . $store->id ) }}" v-for="store of stores" class="border bg-blue-800 cursor-pointer border-gray-200 h-40 relative flex">
                            <div class="h-full w-full object-contain overflow-hidden flex items-center justify-center">
                                @if ( $store->thumb )
                                <img src="{{ $store->thumb }}" alt="{{ $store->name }}">
                                @else
                                <img class="w-24" src="{{ url( '/modules/nsmultistore/assets/images/shop.png' ) }}" alt="{{ $store->name }}">
                                @endif
                            </div>
                            <div class="h-16 bottom-0 absolute w-full z-10 p-2 flex items-center flex-col justify-center text-white font-semibold" style="background: rgb(0,0,0);
    background: linear-gradient(0deg, rgba(0,0,0,0.8379726890756303) 0%, rgba(0,0,0,0.7147233893557423) 32%, rgba(0,212,255,0) 100%);">
                                <span>{{ $store->name }}</span>
                            </div>
                        </a>
                        @endif
                    </div>
                    @endforeach
                </div>
                @else
                <h2 class="font-bold text-center text-3xl text-gray-800">{{ __m( 'Store Selection', 'NsMultiStore' ) }}</h2>
                <p class="text-gray-700 text-center my-4">{{ __m( 'Looks like you don\'t any access to the available store, or the store you\'re allowed to access no longer exists.', 'NsMultiStore' ) }}<br>{{ __m( 'Please contact the administrator.', 'NsMultiStore' ) }}</p>
                @endif
            </div>
            <div class="flex items-center justify-center py-4">
                <a class="text-blue-500 hover:underline" href="{{ ns()->route( 'ns.logout' ) }}">{{ __m( 'Logout', 'NsMultiStore' ) }}</a>
            </div>
        </div>
    </div>
</div>
@endsection