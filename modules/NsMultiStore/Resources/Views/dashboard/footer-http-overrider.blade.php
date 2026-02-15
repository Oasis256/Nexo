@section( 'layout.dashboard.footer' )
    @parent
    @if( ns()->store->isMultiStore() )
    <script>
    ns.storeID              =   '{{ ns()->store->getCurrentStore()->id }}';
    ns.subDomainEnabled     =   <?php echo ns()->store->subDomainsEnabled() ? 'true' : 'false';?>;
    </script>
    <script type="module" src="{{ asset( '/modules/nsmultistore/js/http-overrider.js' ) }}"></script>
    @endif
@endsection