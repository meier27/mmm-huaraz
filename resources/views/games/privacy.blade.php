<x-games.shell title="Aviso de privacidad · Juegos bíblicos" heading="Aviso de privacidad de los juegos" icon="shield">
    <div class="prose-content mt-8 max-w-3xl space-y-6 text-ink/85">
        <section>
            <h2 class="text-xl font-bold text-primary">Quién es responsable</h2>
            <p>{{ $site->church_name }} administra los Juegos bíblicos de este sitio. Para cualquier consulta sobre tus datos, escríbenos desde la página de <a href="{{ route('contact') }}" class="font-semibold text-brand-strong underline">contacto</a>.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold text-primary">Qué datos pedimos y para qué</h2>
            <ul class="list-disc space-y-1 ps-5">
                <li><strong>Apodo:</strong> para mostrar tu avance en el ranking y en tu tarjeta de logros.</li>
                <li><strong>Nombre:</strong> solo para que el equipo pueda entregarte un premio. No se publica.</li>
                <li><strong>Correo y contraseña:</strong> para ingresar y recuperar tu contraseña. No se publican, no enviamos publicidad y la contraseña se guarda cifrada.</li>
                <li><strong>Tu avance</strong> (niveles, estrellas y tiempos) y los <strong>premios</strong> que reclames.</li>
            </ul>
        </section>
        <section>
            <h2 class="text-xl font-bold text-primary">Edad</h2>
            <p>Las cuentas son para personas de 14 años o más. Los menores de 14 pueden jugar los primeros niveles sin crear cuenta (no se guarda ningún dato).</p>
        </section>
        <section>
            <h2 class="text-xl font-bold text-primary">Qué es público</h2>
            <p>Solo tu apodo y tus logros. En «Mi avance» puedes ocultar tu perfil: dejarás de aparecer en el ranking y tu tarjeta ya no se podrá ver.</p>
        </section>
        <section>
            <h2 class="text-xl font-bold text-primary">Tus derechos</h2>
            <p>Puedes corregir tu apodo y tu nombre, y borrar tu cuenta con todo tu avance cuando quieras desde «Mi avance». De los premios ya canjeados solo queda el registro con tu apodo, como constancia de la entrega.</p>
        </section>
    </div>
</x-games.shell>
