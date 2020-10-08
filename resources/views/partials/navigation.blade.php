<nav class="p-6 bg-blue-500">
    <div class="container flex flex-wrap items-center justify-between mx-auto">
        <div class="flex items-center flex-shrink-0 mr-6 text-white hover:text-orange-200">
            <i class="mr-2 text-2xl fa fa-users" aria-hidden="true"></i>
            <a href="{{ route('home') }}"
               class="text-3xl font-semibold">{{ config('app.name', 'Vehikl Growth Sessions') }}</a>
        </div>
        <div class="block lg:hidden">
            <button
                class="flex items-center px-3 py-2 text-white border border-white rounded hover:text-orange-200 hover:border-orange-200"
                onclick="document.getElementById('nav-links').classList.toggle('hidden')"
                @click="isExpanded = !isExpanded">
                <svg class="w-3 h-3 fill-current" viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg">
                    <title>
                        Menu
                    </title>
                    <path d="M0 3h20v2H0V3zm0 6h20v2H0V9zm0 6h20v2H0v-2z"/>
                </svg>
            </button>
        </div>
        <div
            class="justify-end hidden w-full text-xl text-center uppercase lg:flex lg:items-center lg:w-auto"
            id="nav-links">
            <div class="flex flex-col items-center justify-center text-xl lg:flex-row">
                @guest
                    <a href="{{route('oauth.login.redirect')}}"
                       class="flex items-center mt-4 mr-6 text-white lg:mt-0 hover:text-orange-200 ">
                        <i class="mr-4 text-3xl fa fa-github" aria-hidden="true"></i> Login
                    </a>
                @endguest
                @auth
                    <a href="{{ route('activity') }}">
                        <v-avatar class="mr-4"
                                  container-class="border border-transparent hover:border-orange-200"
                                  src="{{ auth()->user()->avatar }}"
                                  alt="Your Avatar"
                                  size="12"></v-avatar>
                    </a>
                    <a href="{{ route('logout') }}"
                       onclick="event.preventDefault(); document.getElementById('logout-form').submit();"
                       class="flex items-center mt-4 text-white lg:mt-0 hover:text-orange-200 ">
                        Logout
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                @endauth
                <a href="{{route('about')}}"
                   class="mt-4 ml-6 text-white lg:mt-0 hover:text-orange-200 ">About
                </a>
            </div>
        </div>
    </div>
</nav>
