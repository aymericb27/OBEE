<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="description" content="Brief description of the page">
    <title>OBEE</title>

    <!-- Favicon (facultatif) -->
    <link rel="icon" href="favicon.ico" type="image/x-icon">

    <!-- Feuilles de style CSS -->

    @vite('resources/js/app.js')

    @vite('resources/css/bootstrap_extend.css')
    <!-- Polices, icônes ou autres ressources -->
    <script src="https://kit.fontawesome.com/32b77cab3e.js" crossorigin="anonymous"></script>
    <link href="https://fonts.googleapis.com/css2?family=Inter&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.2/dist/css/bootstrap.min.css"
        integrity="sha384-xOolHFLEh07PJGoPkLv1IbcEPTNtaed2xpHsD9ESMhqIYd0nLMwNLD69Npy4HI+N" crossorigin="anonymous">
    @vite('resources/css/app.css')

</head>

<body>
    <!-- En-tête -->

    <!-- Contenu principal -->
    <main>
        @yield('content')
    </main>

    <!-- Pied de page -->
    <footer>
    </footer>

    <!-- Scripts JS -->
</body>

</html>
