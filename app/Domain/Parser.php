<?php

namespace App\Domain;


class Parser implements \Iterator
{
    /**
     * XML parser resource
     *
     * @var resource|null
     */
    protected $parser = null;

    /**
     * Current position for iteration
     *
     * @var int
     */
    protected int $position = 0;

    /**
     * Parsed OPML content as array
     *
     * @var array
     */
    protected array $opmlContents = [];

    /**
     * Raw unparsed OPML string
     *
     * @var string
     */
    protected string $unparsedOpml = '';

    /**
     * Mapping of OPML outline attributes to internal property names
     *
     * @var array
     */
    protected array $opmlMapVars = [
        'ID' => 'id',                      // Unique element ID
        'TYPE' => 'type',                  // Element type (audio, feed, playlist, etc)
        'URL' => 'url',                    // Location of the item
        'HTMLURL' => 'html_url',           // Top-level link element
        'TEXT' => 'title',                 // Specifies the title of the item
        'TITLE' => 'title',                // Specifies the title of the item
        'LANGUAGE' => 'language',          // The value of the top-level language element
        'TARGET' => 'link_target',         // The target window of the link
        'VERSION' => 'version',            // RSS version information
        'DESCRIPTION' => 'description',    // The top-level description element from the feed
        'XMLURL' => 'xml_url',             // The http address of the feed
        'CREATED' => 'created',            // Date-time that the outline node was created
        'IMAGEHREF' => 'imageHref',        // A link to an image related to the element
        'ICON' => 'icon',                  // A link to an icon related to the element
        'F' => 'song',                     // Song filename in OPML playlists
        'BITRATE' => 'bitrate',            // Bitrate of an audio stream, in kbps
        'MIME' => 'mime',                  // MIME type of the stream/file
        'DURATION' => 'duration',          // Playback duration in seconds
        'LISTENERS' => 'listeners',        // Number of current listeners
        'CURRENT_TRACK' => 'current_track',// Currently playing track
        'GENRE' => 'genre',                // Stream genre
        'SOURCE' => 'source',              // Audio source information
    ];

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->reset();
    }

    /**
     * Reset parser state
     *
     * @return void
     */
    public function reset():void
    {
        $this->parser = null;
        $this->opmlContents = [];
        $this->position = 0;
        $this->unparsedOpml = '';
    }

    /**
     * Rewind the iterator to the beginning
     *
     * @return void
     */
    public function rewind(): void
    {
        $this->position = 0;
    }

    /**
     * Return the current element
     *
     * @return mixed The current element
     */
    public function current(): mixed
    {
        return $this->opmlContents[$this->position] ?? null;
    }

    /**
     * Return the key of the current element
     *
     * @return int The key of the current element
     */
    public function key(): int
    {
        return $this->position;
    }

    /**
     * Move the iterator to the next entry
     *
     * @return void
     */
    public function next(): void
    {
        ++$this->position;
    }

    /**
     * Check if current position is valid
     *
     * @return bool Returns TRUE if the current position is valid
     */
    public function valid(): bool
    {
        return isset($this->opmlContents[$this->position]);
    }

    /**
     * Fetch contents from file or URL using cURL or file_get_contents
     *
     * @param string $location The location (file or URL) of the OPML file
     * @param resource|null $context Stream context
     * @return string Contents of the OPML file
     * @throws \RuntimeException If file cannot be fetched
     */
    protected function getOPMLFile(string $location, $context = null): string
    {
        if (empty($location)) {
            throw new \InvalidArgumentException('OPML location cannot be empty');
        }

        if (extension_loaded('curl')) {
            $options = [
                CURLOPT_RETURNTRANSFER => true,     // return web page
                CURLOPT_HEADER         => false,    // don't return headers
                CURLOPT_FOLLOWLOCATION => true,     // follow redirects
                CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
                CURLOPT_ENCODING       => "",       // handle compressed
                CURLOPT_USERAGENT      => "Mozilla/5.0 LaravelCompany/News", // user agent
                CURLOPT_AUTOREFERER    => true,     // set referrer on redirect
                CURLOPT_CONNECTTIMEOUT => 120,      // time-out on connect
                CURLOPT_TIMEOUT        => 120,      // time-out on response
                CURLOPT_SSL_VERIFYPEER => true,     // verify SSL certificates
            ];

            $ch = curl_init($location);
            curl_setopt_array($ch, $options);
            $contents = curl_exec($ch);

            if ($contents === false) {
                $error = curl_error($ch);
                curl_close($ch);
                throw new \RuntimeException("Failed to fetch OPML: $error");
            }

            curl_close($ch);
            return $contents;
        }

        $contents = @file_get_contents($location, false, $context);
        if ($contents === false) {
            throw new \RuntimeException("Failed to fetch OPML from: $location");
        }
        return $contents;
    }

    /**
     * Handle XML element start tags
     *
     * @param resource $parser XML parser reference
     * @param string $tagName Element name
     * @param array $attrs Element attributes
     * @return void
     */
    protected function parseElementStart($parser, string $tagName, array $attrs): void
    {
        // Only process OUTLINE tags
        if ($tagName === 'OUTLINE') {
            $node = [];

            // Map attributes according to the defined mapping
            foreach (array_keys($this->opmlMapVars) as $key) {
                if (isset($attrs[$key])) {
                    $mappedKey = $this->opmlMapVars[$key];
                    $node[$mappedKey] = $attrs[$key];
                }
            }

            $this->opmlContents[] = $node;
        }
    }

    /**
     * Handle XML element end tags
     *
     * @param resource $parser XML parser reference
     * @param string $tagName Element name
     * @return void
     */
    protected function parseElementEnd($parser, string $tagName): void
    {
        // Can be extended in child classes if needed
    }

    /**
     * Handle XML character data
     *
     * @param resource $parser XML parser reference
     * @param string $data Character data
     * @return void
     */
    protected function parseElementCharData($parser, string $data): void
    {
        // Can be extended in child classes if needed
    }

    /**
     * Parse XML data
     *
     * @param string $xmlData XML content to parse
     * @return void
     * @throws \RuntimeException If XML parsing fails
     */
    protected function parse(string $xmlData): void
    {
        // Reset contents and position
        $this->opmlContents = [];
        $this->position = 0;

        // Create XML parser with proper encoding
        $this->parser = xml_parser_create('UTF-8');

        // Configure parser options
        xml_parser_set_option($this->parser, XML_OPTION_CASE_FOLDING, true);
        xml_parser_set_option($this->parser, XML_OPTION_SKIP_WHITE, true);

        // Set handler object and callbacks
        xml_set_object($this->parser, $this);
        xml_set_element_handler(
            $this->parser,
            [$this, 'parseElementStart'],
            [$this, 'parseElementEnd']
        );
        xml_set_character_data_handler(
            $this->parser,
            [$this, 'parseElementCharData']
        );

        // Parse the XML data
        if (!xml_parse($this->parser, $xmlData)) {
            $errorCode = xml_get_error_code($this->parser);
            $errorMsg = xml_error_string($errorCode);
            $line = xml_get_current_line_number($this->parser);

            xml_parser_free($this->parser);

            throw new \RuntimeException(
                "XML parsing error: $errorMsg at line $line"
            );
        }

        xml_parser_free($this->parser);
    }

    /**
     * Parse OPML from a file or URL location
     *
     * @param string $location File path or URL
     * @param resource|null $context Stream context
     * @return void
     */
    public function parseLocation(string $location, $context = null): void
    {
        $this->unparsedOpml = trim($this->getOPMLFile($location, $context));
        $this->parse($this->unparsedOpml);
    }

    /**
     * Parse OPML from a string
     *
     * @param string $opml OPML content
     * @return void
     */
    public function parseOpml(string $opml): void
    {
        $this->unparsedOpml = trim($opml);
        $this->parse($this->unparsedOpml);
    }

    /**
     * Get the unparsed OPML string
     *
     * @return string The unparsed OPML string
     */
    public function getUnparsedOpml(): string
    {
        return $this->unparsedOpml;
    }

    /**
     * Add or replace an OPML attribute mapping
     *
     * @param string $attribute The attribute name to map
     * @param string $mappedAttribute The internal name to map to
     * @return self For method chaining
     */
    public function setAttribute(string $attribute, string $mappedAttribute = ''): self
    {
        $attribute = strtoupper(preg_replace('/\s+/', '_', trim($attribute)));

        if (empty($mappedAttribute)) {
            $mappedAttribute = strtolower($attribute);
        } else {
            $mappedAttribute = strtolower(preg_replace('/\s+/', '_', trim($mappedAttribute)));
        }

        $this->opmlMapVars[$attribute] = $mappedAttribute;
        return $this;
    }

    /**
     * Remove an attribute from the mapping
     *
     * @param string $attribute The attribute to remove
     * @return self For method chaining
     */
    public function unsetAttribute(string $attribute): self
    {
        $attribute = strtoupper(preg_replace('/\s+/', '_', trim($attribute)));
        unset($this->opmlMapVars[$attribute]);
        return $this;
    }

    /**
     * Get all mapped attributes
     *
     * @return array The attribute mapping array
     */
    public function getAttributes(): array
    {
        return $this->opmlMapVars;
    }

    /**
     * Get parsed OPML content
     *
     * @return array The parsed OPML content
     */
    public function getOpmlContents(): array
    {
        return $this->opmlContents;
    }

    /**
     * Count the number of outline elements
     *
     * @return int Number of outline elements
     */
    public function count(): int
    {
        return count($this->opmlContents);
    }
}
