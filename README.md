# lda

Hack で LDA を実装してみたものです。

### 実行方法

勉強会で利用したデータファイルが data/in1.csv に置かれているとして、次のように実行します。

    $ hhvm ./src/lda.hh data/in1.csv 10 100 0.1 0.01 >data/in1_result.txt

引数の意味は以下のとおりです。ファイル名とトピック数は必須です。

    $ hhvm ./src/lda.hh
    Usage: ./src/lda.hh filename k [iter [alpha beta]]

実行結果は標準出力に書き出されるのでリダイレクトして保存してください。

最後の iteration でのトピック割り当てを集計して得られた doc-topic 頻度と topic-word 頻度が出力されます。
入力データの文書数を N として、先頭から N 行が doc-topic 頻度です。残りが topic-word 頻度です。
次のようにして分割します (勉強会で使った in1.csv は 366 文書のコーパスでした)。

    $ head -n 366 data/in1_result.txt >data/in1_result_dt.txt
    $ tail -n +367 data/in1_result.txt >data/in1_result_tw.txt

### term-score を用いてトピックに特徴的な単語を出力する

topic-word の頻度にパラメータβを加えて正規化すると、P(w|t) が得られます。

    $ ./scripts/calc_term_probability.sh data/in1_result_tw.txt 0.01 >data/in1_result_tw_prob.txt

それを元に term-score という値を計算します。
頻出語はどのトピックからもよく生成されるので、P(w|t) そのものを使うと、トピックに特徴的な単語をうまく抽出できません。
term-score という指標を使うことで、トピックに特徴的な単語を得られるようです。

    $ ./scripts/calc_term_score.awk <data/in1_result_tw_prob.txt >data/in1_result_termscore.txt

各トピックについて term-score の降順にソートして、トピックに特徴的な単語を出力します。
上位の何語を出力するかを第二引数に指定します。

    $ ./scripts/show_top_words.sh data/in1_result_termscore.txt 10

### doc-topic 頻度を用いて類似文書を出力する

doc-topic の頻度にパラメータαを加えて正規化すると、P(t|d) が得られます。

    $ ./scripts/calc_topic_probability.awk -valpha=0.1 <data/in1_result_dt.txt >data/in1_result_dt_prob.txt

これを文書の特徴量と考えることで、文書分類や類似文書検索に応用できます。
次のスクリプトでは、コーパスから基準となる文書を選び、それとの距離の近い順にソートすることで、似ている文書を出力します。

    $ ./scripts/show_document_similarity.sh data/in1_head.txt 1 data/in1_result_dt_prob.txt

距離の計算には JS ダイバージェンスを使っています。

第一引数の in1_head.txt は各文書の見出しを列挙したテキストファイルです。これは結果表示のために用いるだけで、距離の計算には関係しません。
第二引数に基準となる文書を指定し、第三引数に P(t|d) のファイルを指定します。

### そのほか

read_documents.hh の代わりに read_documents_raw.hh を使うと、MeCab で分かち書きした状態のファイル (1 行 1 文書、空白区切り) を読み込めます。

show_document_similarity.sh の中で calc_js_divergence.awk を実行している部分を calc_hellinger_distance.awk に変えると、ヘリンジャー距離を使って計算できます。
