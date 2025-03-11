<?php

return [
    'third_party_services' => 'Serviços de terceiros',
    'third_party_services_desc' => 'Esta página destina-se a armazenar as credenciais de serviços de terceiros.',
    'create_app' => 'Criar uma aplicação em :name',
    'configure_services_desc' => 'Clique no botão abaixo para configurar os serviços de terceiros.',
    'configure_services' => 'Configurar serviços',
    'representative_data' => 'Estes dados não são reais, apenas representativos.',
    'not_configured_service' => 'Não configurou o serviço :service.',
    'service_saved' => 'O serviço :service foi guardado',
    'tenor' => [
        'search_gifs' => 'Pesquisar GIFs de Tenor',
        'gif' => 'GIF de Tenor',
        'author' => 'Autor',
        'use_gif' => 'Com o Tenor, pode utilizar GIFs diretamente no Mixpost.'
    ],
    'unsplash' => [
        'search' => 'Pesquisar Unsplash',
        'can_use_external_photos' => 'Com o Unsplash, pode utilizar fotografias de arquivo externas diretamente no Mixpost.'
    ],
    'meta' => [
        'reel_supports_one_video' => 'O Reels só suporta um vídeo',
        'reel_only_video_allowed' => 'O Reel só suporta vídeo.',
        'error' => [
            'session_expired' => 'A sessão expirou. Tente publicar novamente.',
            'media_already_published' => 'O ficheiro multimédia já foi publicado.',
            'publication_video_expired' => 'O vídeo da publicação expirou',
            'required_param_missing' => 'Falta um parâmetro necessário na chamada à API.'
        ],
        'post' => 'Publicar',
        'reel' => 'Carretel',
        'story_single_media_limit' => 'A história só suporta uma imagem ou vídeo',
        'story' => 'História'
    ],
    'facebook' => [
        'connect_group' => 'Ligar um novo grupo do Facebook',
        'connect_page' => 'Ligar uma nova página do Facebook',
        'report' => [
            'number_members_per_day' => 'O número de membros por dia durante o período selecionado',
            'number_times_posts_impressions' => 'O número de vezes que as publicações da sua Página entraram no ecrã de uma pessoa. As publicações incluem estados, fotografias, ligações, vídeos e muito mais.',
            'page_engaged_users' => 'Utilizadores envolvidos na página',
            'post_engagements' => 'Envolvimentos com publicações',
            'posts_impressions' => 'Impressões de publicações',
            'number_people_page' => 'O número de pessoas que se envolveram com a sua Página. O envolvimento inclui qualquer clique.',
            'number_times_post_engagements' => 'O número de vezes que as pessoas se envolveram com as suas publicações através de reacções, comentários, partilhas e muito mais.'
        ],
        'page_options' => 'Opções da página do Facebook'
    ],
    'instagram' => [
        'connect_account' => 'Ligar uma nova conta empresarial do Instagram',
        'report' => [
            'number_comments_posts' => 'O número de comentários nas suas publicações',
            'engagement_report' => 'Soma de contactos de e-mail, chamadas telefónicas, cliques na direção, cliques em mensagens de texto e cliques no Web site',
            'engagement' => 'Envolvimento',
            'clicks_divided_impressions' => '(Contactos de e-mail + Cliques em chamadas telefónicas + Cliques em direcções + Cliques em mensagens de texto + Cliques no Web site] dividido por Impressões',
            'engagement_rate' => 'Taxa de envolvimento',
            'number_taps_email' => 'Número total de toques na ligação de correio eletrónico no perfil',
            'email_contacts' => 'Contactos de e-mail',
            'number_new_followers' => 'Número total de novos seguidores',
            'follower_count' => 'Contagem de seguidores',
            'number_directions_clicks' => 'Número total de toques na ligação de direcções no perfil',
            'directions_clicks' => 'Cliques em direcções',
            'number_phone_call_clicks' => 'Número total de toques na ligação de chamada no perfil',
            'phone_call_clicks' => 'Cliques em chamadas telefónicas',
            'number_profile_views' => 'Número total de utilizadores que visualizaram o perfil',
            'profile_views' => 'Visualizações do perfil',
            'number_reach' => 'Número total de utilizadores únicos que visualizaram pelo menos um dos itens multimédia',
            'reach' => 'Alcance',
            'number_text_message_clicks' => 'Número total de toques na hiperligação da mensagem de texto no perfil',
            'text_message_clicks' => 'Cliques em mensagens de texto',
            'number_website_clicks' => 'Número total de toques na ligação para o sítio Web no perfil',
            'website_clicks' => 'Cliques no sítio Web'
        ],
        'select_video_image' => 'Selecionar um vídeo ou uma imagem para esta conta do Instagram.'
    ],
    'linkedin' => [
        'connect_page' => 'Ligar uma nova página do LinkedIn',
        'connect_profile' => 'Ligar um novo perfil do LinkedIn',
        'visibility' => 'Visibilidade',
        'public' => 'Público',
        'connections' => 'Ligações',
        'not_support_accounts' => 'Infelizmente, a API do LinkedIn não suporta a apresentação de análises para contas pessoais do LinkedIn.',
        'insights' => 'Informações',
        'job_title' => 'O seu cargo',
        'send' => 'Enviar'
    ],
    'tiktok' => [
        'supports_only_videos' => 'O TikTok só suporta ficheiros de vídeo.',
        'connect_profile' => 'Ligar um novo perfil TikTok',
        'disclose' => 'Definição de divulgação de conteúdos',
        'your_brand' => 'A sua marca',
        'branded_content' => 'Conteúdo da marca',
        'who_watch_video' => 'Quem pode ver este vídeo',
        'direct_share_type' => 'Os vídeos do TikTok são enviados para a caixa de entrada para edição e publicação a partir da aplicação TikTok. O texto não será enviado.',
        'for_you' => 'Para si',
        'select_video' => 'Selecionar um vídeo para esta conta TikTok.',
        'everyone' => 'Todos',
        'friends' => 'Amigos',
        'only_you' => 'Apenas tu',
        'followers' => 'Seguidores',
        'report' => [
            'views_video' => 'O número de vezes que os seus vídeos foram vistos',
            'views' => 'Visualizações',
            'number_shares' => 'O número de vezes que os seus vídeos foram partilhados',
            'shares' => 'Partilhas',
            'number_comments' => 'O número total de comentários'
        ],
        'video_limit' => 'Só é permitido um vídeo por publicação.',
        'disclose_desc' => 'Ativar para revelar que este vídeo promove bens ou serviços em troca de algo de valor. O seu vídeo pode promover-se a si próprio, a um terceiro ou a ambos.',
        'promo_video_alert' => 'O seu vídeo será rotulado como "Conteúdo promocional"',
        'branded_content_desc' => 'Está a promover outra marca ou um terceiro. Este vídeo será classificado como Conteúdo de marca.',
        'your_brand_desc' => 'Está a promover-se a si próprio ou ao seu próprio negócio. Este vídeo será classificado como Conteúdo orgânico da marca.',
        'your_brand_accept_terms' => 'Ao publicar, você concorda com a <a href=":href" target="_blank" class="link">Confirmação de uso de música</a> do TikTok.',
        'accept_terms' => 'Ao publicar, você concorda com a <a href=":href" target="_blank" class="link">Política de conteúdo de marca</a> e <a href=":href_music" target="_blank" class="link">Confirmação de uso de música</a> do TikTok.”',
        'partner_video_alert' => 'O teu vídeo será rotulado como “Parceria paga”. ',
        'visibility_branded_content' => 'A visibilidade do conteúdo de marca não pode ser privada. Selecione a visibilidade "Everyone/Friends" para habilitar essa opção.',
        'allow_users' => 'Permitir que os utilizadores',
        'comment' => 'Comentar',
        'duet' => 'Dueto',
        'stitch' => 'Stitch',
        'branded_no_private' => 'A visibilidade do conteúdo de marca não pode ser definida como privada.',
        'content_disclosure_required' => 'É necessário indicar se o conteúdo se promove a si próprio, a terceiros ou a ambos.',
        'privacy_level_required' => 'Parece que não escolheu quem pode ver o seu vídeo.'
    ],
    'pinterest' => [
        'not_support_video' => 'O Pinterest não está a suportar temporariamente o carregamento de vídeos.',
        'video_upload_failed' => 'Erro ao carregar o vídeo. Tente novamente mais tarde ou tente outro vídeo.',
        'connect_profile' => 'Ligar um novo perfil do Pinterest',
        'board_created' => 'O quadro foi criado com êxito',
        'board_not_added' => 'Não foi possível adicionar o quadro',
        'board_name' => 'Nome do quadro',
        'create_board' => 'Criar quadro do Pinterest',
        'save_rate' => 'Guardar taxa',
        'not_account' => 'Esta não é uma conta do Pinterest',
        'report' => [
            'number_pins_saved' => 'O número de vezes que os seus pins foram guardados',
            'number_pin_clicks' => 'O número total de cliques no seu Pin ou anúncio para que seja aberto em grande plano.',
            'pin_clicks' => 'Cliques em pins',
            'number_impressions' => 'O total de salvamentos dos seus Pins dividido pelo número total de vezes que os seus Pins estiveram no ecrã.',
            'number_outbound_clicks' => 'O número de vezes que as pessoas realizam acções que as conduzem a um destino fora do Pinterest.',
            'outbound_clicks' => 'Cliques de saída'
        ],
        'select_board_for' => 'Selecionar um quadro para <span class="font-semibold mr-1">:account</span>'
    ],
    'twitter' => [
        'connect_profile' => 'Ligar um novo perfil X',
        'reports_limited' => 'Utiliza a API gratuita do X. Os relatórios podem ser limitados.',
        'upgrade' => 'Atualizar o nível da API do X',
        'number_retweets' => 'O número de vezes que seus tweets foram retweetados',
        'retweets' => 'Retweets',
        'upload_failed' => 'Falha ao carregar o ficheiro',
        'form' => [
            'edit_app' => 'Terá de editar as permissões da aplicação e permitir "Ler e escrever"'
        ]
    ],
    'youtube' => [
        'connect_profile' => 'Ligar um novo perfil do YouTube',
        'public' => 'Público',
        'private' => 'Privado',
        'unlisted' => 'Não listado',
        'analytics_under_development' => 'A análise do YouTube está a ser desenvolvida.',
        'select_video' => 'Selecionar um vídeo para esta conta do YouTube.',
        'privacy_config' => 'Configuração de privacidade',
        'title' => 'Vídeo ou título curto'
    ],
    'mastodon' => [
        'upload_failed' => 'Não foi possível carregar o ficheiro multimédia para o servidor.',
        'connect_profile' => 'Ligar um novo perfil Mastodon',
        'enter_server' => 'Introduzir o seu servidor Mastodon',
        'mark_media' => 'Marcar os média como sensíveis',
        'report' => [
            'number_replies' => 'O número de respostas às suas mensagens',
            'replies' => 'Respostas',
            'number_reblogs' => 'O número de vezes que as suas mensagens foram re-blogadas',
            'reblogs' => 'Reblogs',
            'favourites_number' => 'O número de vezes que as suas publicações foram adicionadas aos favoritos',
            'favourites' => 'Favoritos'
        ]
    ],
    'provider_options' => 'Opções de :provider',
    'services' => 'Serviços',
    'threads' => [
        'connect' => 'Ligar um novo perfil do Threads'
    ]
];
