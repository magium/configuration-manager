[![Scrutinizer Code Quality](https://scrutinizer-ci.com/g/magium/configuration-manager/badges/quality-score.png?b=develop)](https://scrutinizer-ci.com/g/magium/configuration-manager/?branch=develop) 
[![Code Coverage](https://scrutinizer-ci.com/g/magium/configuration-manager/badges/coverage.png?b=develop)](https://scrutinizer-ci.com/g/magium/configuration-manager/?branch=develop)
[![Build Status](https://scrutinizer-ci.com/g/magium/configuration-manager/badges/build.png?b=develop)](https://scrutinizer-ci.com/g/magium/configuration-manager/build-status/develop)
# The Magium Configuration Manager

The Magium Configuration Manager provides a configuration setup that is similar to Magento's configuration management.  There are 3 

A library for managing context-based configuration for PHP applications, currently a work in progress.  It is meant to be functionality similar to how Magento manages configuration.  It was born out of frustration with having to deal with YAML, PHP and/or XML files that may or may not follow a well-defined, predictable pattern.  The goal of this project is to provide 1) an embeddable frontend UI that can be used to manage application settings, 2) simple programmer level access to individual configuration settings both for PHP code and also via a REST-like interface, 3) inheritable configuration contexts, 4) Configuration merging from multiple different sources (e.g. multiple XML, PHP, YAML, or JSON files for different ad-hoc modules).

Point number 3 is the main impetus.  I got tired of having to write configuration for production and development, for different programs.  So my hope is to create an easy to use configuration management program that make managing configuration much, much easier.

Configuration is managed on 3 levels: section, group, and element.  These are used to determine 1) UI elements for managing configuration, and 2) an easy way to reference individual configuration data.

I don't have the working code done yet, but it would like something like this:

### Configuration File 1

```
<?xml version="1.0" encoding="utf-8"?>
<configuration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration configuration-element.xsd">
    <section id="general" name="General">
        <group id="website" name="Website">
            <element id="title" name="Title">
                <description>This value is used for the title of the website</description>
                <value>My Homepage</value>
            </element>
            <element id="languages" name="Languages" source="Namespace\MySource" type="multi"/>
        </group>
    </section>
    <section id="security" name="Security">
        <group id="authentication" name="Authentication">
            <element id="require" name="Require Authentication" type="select" source="Magium\Configuration\Source\YesNo"/>
        </group>
    </section>
</configuration>
```

### Configuration File 2

```
<?xml version="1.0" encoding="utf-8"?>
<configuration xmlns="http://www.magiumlib.com/Configuration"
          xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
          xsi:schemaLocation="http://www.magiumlib.com/Configuration configuration-element.xsd">
    <section id="general" name="General">
        <group id="website" name="Website">
            <element id="title" name="Title">
                <description>This value is used for the title of the website</description>
                <value>My Homepage</value>
            </element>
            <element id="languages" name="Languages" source="Namespace\MySource" type="multi"/>
        </group>
        <group id="design" name="Design">
            <element id="theme" name="Theme"/>
        </group>
    </section>
</configuration>
```

### (Pseudo) code to retrieve a configuration value

```
$config = $configManager->getConfig(getenv('ENVIRONMENT'));
$siteTitle = $config->getValue('general/website/title');
```

### Moving forward

Here are a list of features that I want to have included

* An HTML based UI for making configuration changes
  * Embeddable user restrictions
  * ACL resources
* A local/remote cache system that caches the configuration locally, but synchronizes with a remote cache
* Integrations/adapters with multiple third party Dependency Injection Containers, allowing you to either manage DI configuration centrally, and/or, modify DI configuration on the fly without requiring a deployment.
* Oh yes, and integration with persistent storage
