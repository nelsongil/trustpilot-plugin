<?php
// includes/github-updater.php

if ( ! class_exists( 'CTR_GitHub_Updater' ) ) {
    class CTR_GitHub_Updater {

        private $slug;
        private $plugin_file;
        private $github_repo_owner;
        private $github_repo_name;
        private $current_version;
        private $cache_key;
        private $cache_duration; // Cache duration in seconds

        public function __construct( $plugin_file, $github_repo_owner, $github_repo_name ) {
            $this->plugin_file     = $plugin_file;
            $this->slug            = basename( dirname( $plugin_file ) ); // Should be trustpilot-reviews
            $this->github_repo_owner = $github_repo_owner;
            $this->github_repo_name  = $github_repo_name;
            $this->cache_key       = 'ctr_github_updater_' . md5($github_repo_owner . '/' . $github_repo_name);
            $this->cache_duration  = DAY_IN_SECONDS; // Cache for 24 hours by default

            // Get current plugin version from its header
            $plugin_data = get_file_data( $this->plugin_file, array( 'Version' => 'Version' ) );
            $this->current_version = $plugin_data['Version'];

            add_filter( 'plugins_api', [ $this, 'info' ], 20, 3 );
            add_filter( 'site_transient_update_plugins', [ $this, 'update' ] );
            add_action( 'upgrader_process_complete', [ $this, 'purge' ], 10, 2 );
        }

        public function request() {
            $remote = get_transient( $this->cache_key );

            if ( false === $remote ) {
                $api_url = sprintf( 'https://api.github.com/repos/%s/%s/releases/latest', $this->github_repo_owner, $this->github_repo_name );

                $args = array(
                    'timeout' => 10,
                    'headers' => array(
                        'Accept'     => 'application/vnd.github.v3+json',
                        'User-Agent' => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
                    ),
                );

                // Add GitHub Token if available (for private repos or higher API limits)
                // if ( defined( 'CTR_GITHUB_TOKEN' ) && CTR_GITHUB_TOKEN ) {
                //     $args['headers']['Authorization'] = 'token ' . CTR_GITHUB_TOKEN;
                // }

                $remote = wp_remote_get( $api_url, $args );

                if ( is_wp_error( $remote ) || 200 !== wp_remote_retrieve_response_code( $remote ) || empty( wp_remote_retrieve_body( $remote ) ) ) {
                    return false;
                }

                set_transient( $this->cache_key, $remote, $this->cache_duration );
            }

            $remote_body = json_decode( wp_remote_retrieve_body( $remote ) );

            // Extract required data from GitHub API response
            if ( ! empty( $remote_body ) ) {
                $data = new stdClass();
                $data->version      = $remote_body->tag_name; // Tag name is usually the version
                $data->download_url = $remote_body->zipball_url; // URL to download the zip
                $data->slug         = $this->slug;
                $data->last_updated = $remote_body->published_at;
                $data->changelog    = $remote_body->body; // Release notes
                // Add other fields if needed for plugin details page (e.g., author, tested)
                // These would need to be in the GitHub Release body or fetched separately.
                // For simplicity, we'll only provide minimal info for updates.

                return $data;
            }

            return false;
        }

        public function info( $response, $action, $args ) {
            // do nothing if you're not getting plugin information right now
            if ( 'plugin_information' !== $action ) {
                return $response;
            }

            // do nothing if it is not our plugin
            if ( empty( $args->slug ) || $this->slug !== $args->slug ) {
                return $response;
            }

            // get updates
            $remote = $this->request();

            if ( ! $remote ) {
                return $response;
            }

            $response = new stdClass();

            $response->name           = 'Custom Trustpilot Reviews'; // Hardcode or fetch from plugin header
            $response->slug           = $remote->slug;
            $response->version        = $remote->version;
            $response->download_link  = $remote->download_url;
            $response->last_updated   = $remote->last_updated;
            $response->sections = array(
                'changelog' => wp_kses_post( $remote->changelog ), // Display release notes as changelog
            );

            // You might want to add more details like author, tested, requires, requires_php
            // For now, let's keep it minimal for updates to work.

            return $response;
        }

        public function update( $transient ) {
            if ( empty( $transient->checked ) ) {
                return $transient;
            }

            $remote = $this->request();

            if ( $remote && version_compare( $this->current_version, $remote->version, '<' ) ) {
                $response              = new stdClass();
                $response->slug        = $this->slug;
                $response->plugin      = plugin_basename( $this->plugin_file );
                $response->new_version = $remote->version;
                $response->url         = sprintf( 'https://github.com/%s/%s/releases/tag/%s', $this->github_repo_owner, $this->github_repo_name, $remote->version );
                $response->package     = $remote->download_url;

                $transient->response[ $response->plugin ] = $response;
            }

            return $transient;
        }

        public function purge( $upgrader, $options ) {
            if ( 'update' === $options['action'] && 'plugin' === $options['type'] && isset( $options['plugins'] ) ) {
                foreach ( $options['plugins'] as $plugin ) {
                    if ( plugin_basename( $this->plugin_file ) === $plugin ) {
                        delete_transient( $this->cache_key );
                    }
                }
            }
        }
    }
} 