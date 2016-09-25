Installation steps
==================

1.In your project composer.json file "extra" section add the following information

    "extra": {
        "installer-paths": {
            "src/Ibtikar/GlanceUMSBundle/": ["Ibtikar/GlanceUMSBundle"]
        }
    }

2.Require the package using composer by running

    composer require Ibtikar/GlanceUMSBundle

3.Add to your appkernel the next line
    new Ibtikar\ShareEconomyUMSBundle\IbtikarShareEconomyUMSBundle(),

4.Add this route to your routing file

    ibtikar_share_economy_ums:
        resource: "@IbtikarGlanceUMSBundle/Resources/config/routing.yml"
        prefix:   /





5.Add the next line to your .gitignore file

    /src/Ibtikar/GlanceUMSBundle

