<div id="dashboard-header" class="multi:w-full multi:flex multi:flex-col-reverse multi:md:flex-row multi:justify-between multi:p-4">
    <div class="flex items-center my-2 md:my-0">
        <div>
            <div @click="toggleSideMenu()" class="hover:shadow-lg hover:border-opacity-0 border ns-inset-button rounded-full multi:h-10 multi:border multi:border-input-edge multi:w-10 cursor-pointer font-bold text-2xl justify-center items-center flex">
                <i class="las la-bars"></i>
            </div>
        </div>
        <div class="multi:ml-3">
            @if( ns()->store->isMultiStore() )
            <h2 class="font-bold text-2xl text-fontcolor">{{ ns()->store->getCurrentStore()->name }}</h2>
            @else
            <h2 class="font-bold text-2xl text-fontcolor">{{ __m( 'Multi Stores', 'NsMultiStore' ) }}</h2>
            @endif
        </div>
    </div>
    <div class="top-tools-side justify-between md:justify-end flex w-full md:w-auto items-center md:-mx-2">
        <div clss="multi:px-2">
            <ns-notifications></ns-notifications>
        </div>
        @if ( ! ns()->store->subDomainsEnabled() || ! ns()->store->isMultiStore() )
        <div class="multi:px-2">
            <a href="{{ route( 'ns.multistore-stores' ) }}" class="hover:primary hover:shadow-lg hover:border-opacity-0 rounded-full multi:h-12 multi:w-12 cursor-pointer font-bold text-2xl justify-center items-center flex border ns-inset-button">
                <i class="las la-store"></i>
            </a>
        </div>
        <div class="multi:px-2">
            <ns-multistore-selector class="rounded-lg flex border multi:py-2 justify-center hover:border-opacity-0 cursor-pointer hover:shadow-lg ns-inset-button"></ns-multistore-selector>
        </div>
        @endif
        <div class="px-2">
            <div @click="toggleMenu()" :class="menuToggled ? 'toggled border-transparent shadow-lg rounded-t-lg' : 'untoggled rounded-lg'" class="ns-avatar w-32 md:w-56 flex flex-col border py-2 justify-center hover:border-opacity-0 cursor-pointer hover:shadow-lg">
                <ns-avatar 
                    display-name="{{ Auth::user()->username }}"
                    url="{{ Auth::user()->attribute ? Auth::user()->attribute->avatar_link : asset( 'images/user.png' ) }}"></ns-avatar>
            </div>
            <div class="w-32 md:w-56 shadow-lg flex z-10 absolute -mb-2 rounded-br-lg rounded-bl-lg overflow-hidden" v-if="menuToggled">
                <ul class="primary w-full ns-vertical-menu">
                    @if ( ns()->allowedTo([ 'manage.profile' ]) )
                    <li class="hover:text-primary"><a class="block px-2 py-1" href="{{ ns()->route( 'ns.dashboard.users.profile' ) }}"><i class="las text-lg mr-2 la-user-tie"></i> {{ __m( 'Profile', 'NsMultiStore' ) }}</a></li>
                    @endif
                    <li class="hover:text-primary"><a class="block px-2 py-1" href="{{ ns()->route( 'ns.logout' ) }}"><i class="las la-sign-out-alt mr-2"></i> {{ __m( 'Logout', 'NsMultiStore' ) }}</a></li>
                </ul>
            </div>
        </div>
    </div>
</div>

@section( 'layout.dashboard.footer.inject' )
    @parent
    <script>
        const multistoreData    =   {
            subDomainEnabled: <?php echo ns()->store->subDomainsEnabled() ? 'true' : 'false' ?>,
            baseStoreUrl: "{{ url( '/dashboard/store' ) }}",
            multistoreBaseRoute: "{{ route( 'ns.multistore-stores' ) }}",
            baseProtocol: '{{ ns()->store->baseProtocol() }}',
            baseDomainName: '{{ ns()->store->baseDomainName() }}',
            totalStoreCount: '{{ ns()->store->getOpenedAccessibleStores()->count() }}',
        }
    </script>
    @moduleViteAssets( 'Resources/ts/header.ts', 'NsMultiStore' )
@endsection