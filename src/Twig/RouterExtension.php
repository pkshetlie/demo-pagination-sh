<?php
namespace App\Twig;

use App\Routing\UrlGenerator;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class RouterExtension extends AbstractExtension
{
    /**
     * @var UrlGenerator
     */
    private $urlGenerator;

    /**
     * @var array
     */
    private $urls;

    /**
     * @var string|null
     */
    private $host;

    /**
     * RouterExtension constructor.
     *
     * @param UrlGenerator $urlGenerator
     * @param array $urls
     * @param mixed|string $host
     */
    public function __construct(UrlGenerator $urlGenerator, array $urls, $host = null)
    {
        $this->urlGenerator = $urlGenerator;
        $this->urls = $urls;
        $this->host = $host;
    }

    public function getFunctions()
    {
        return array(
            new TwigFunction('url', array($this, 'generateUrl')),
        );
    }

    /**
     * Generate URL from route.
     *
     * @param string $target
     * @param array $parameters
     *
     * @return string
     */
    public function generateUrl($target, array $parameters = array())
    {
        return $this->urlGenerator->generate(
           '',
            $parameters, null);
    }
}
