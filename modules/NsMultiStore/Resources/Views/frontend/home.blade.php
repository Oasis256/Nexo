@extends( 'layout.base' )

@section( 'layout.base.body' )
    <div id="page-container" class="h-full w-full bg-gray-300 flex">
        <div class="container flex-auto flex-col items-center justify-center flex m-4 sm:mx-auto">
            <div class="flex justify-center items-center py-6">
                <img class="w-32" src="{{ asset( 'svg/nexopos-variant-1.svg' ) }}" alt="NexoPOS">
            </div>
            <div class="rounded shadow bg-white w-full md:w-1/2 lg:w-1/3 overflow-hidden">
                <div id="section-header" class="p-4">
                    <p class="text-gray-700 text-center b-8 text-sm">
                    {!! 
                        sprintf(
                            __m( 'You\'re currently seing the front page for the store %s. The below links takes you to relevant resources for managing your store.' ), 
                            '<strong>' . $store->name . '</strong>'
                        ) 
                    !!}
                    </p>
                </div>
                <div class="flex shadow bg-gray-100 border-t">
                    <div class="flex w-1/3"><a class="text-blue-600 text-sm w-full py-2 text-center hover:bg-gray-200" href="{{ ns()->route( 'ns.dashboard.home' ) }}">{{ __( 'Dashboard' ) }}</a></div>
                    <div class="flex w-1/3"><a class="text-blue-600 text-sm w-full py-2 text-center hover:bg-gray-200" href="{{ ns()->route( 'ns.login' ) }}">{{ __( 'Sign In' ) }}</a></div>
                    <div class="flex w-1/3"><a class="text-blue-600 text-sm w-full py-2 text-center hover:bg-gray-200" href="{{ ns()->route( 'ns.register' ) }}">{{ __( 'Sign Up' ) }}</a></div>
                </div>
            </div>
        </div>
    </div>
@endsection